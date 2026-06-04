<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\AttestationRequest;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\SafeNotificationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AttestationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $stagiaire = $request->user()->load('group.filiere');
        abort_unless($stagiaire->isStagiaire(), 403);

        return view('attestations.index', [
            'requests' => $stagiaire->attestationRequests()->latest()->paginate(10),
            'stagiaire' => $stagiaire,
        ]);
    }

    public function store(Request $request, SafeNotificationService $notifier): RedirectResponse
    {
        $stagiaire = $request->user()->load('group.filiere');
        abort_unless($stagiaire->isStagiaire(), 403);

        if (blank($stagiaire->cni)) {
            return back()->withErrors(['cni' => __('messages.attestations.cni_required')]);
        }

        $attestation = AttestationRequest::create([
            'stagiaire_id' => $stagiaire->id,
            'full_name' => $stagiaire->name,
            'filiere' => $stagiaire->filiereName(),
            'cni' => $stagiaire->cni,
        ]);

        User::query()
            ->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])
            ->get()
            ->each(fn (User $admin) => $notifier->send($admin, new SmartCampusNotification(
                __('messages.mail.attestation_admin_title'),
                __('messages.mail.attestation_admin_body', ['name' => $stagiaire->name]),
                route('attestations.manage'),
                'documents'
            )));

        return back()->with('status', __('messages.attestations.sent'));
    }

    public function manage(): View
    {
        return view('attestations.manage', [
            'requests' => AttestationRequest::with(['stagiaire.group.filiere', 'reviewer'])
                ->latest()
                ->paginate(20),
            'pendingCount' => AttestationRequest::where('status', AttestationRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approve(Request $request, AttestationRequest $attestation, SafeNotificationService $notifier): RedirectResponse
    {
        $this->authorizeReview($request);

        $data = $request->validate([
            'surveillant_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $attestation->update([
            'status' => AttestationRequest::STATUS_APPROVED,
            'surveillant_note' => $data['surveillant_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $notifier->send($attestation->stagiaire, new SmartCampusNotification(
            __('messages.mail.attestation_approved_title'),
            __('messages.mail.attestation_approved_body'),
            route('attestations.index'),
            'documents',
            sendMail: true
        ));

        return back()->with('status', __('messages.attestations.approved'));
    }

    public function reject(Request $request, AttestationRequest $attestation, SafeNotificationService $notifier): RedirectResponse
    {
        $this->authorizeReview($request);

        $data = $request->validate([
            'surveillant_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $attestation->update([
            'status' => AttestationRequest::STATUS_REJECTED,
            'surveillant_note' => $data['surveillant_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $notifier->send($attestation->stagiaire, new SmartCampusNotification(
            __('messages.mail.attestation_rejected_title'),
            __('messages.mail.attestation_rejected_body').($attestation->surveillant_note ? ' '.__('messages.common.note').': '.$attestation->surveillant_note : ''),
            route('attestations.index'),
            'documents',
            sendMail: true
        ));

        return back()->with('status', __('messages.attestations.rejected'));
    }

    public function download(Request $request, AttestationRequest $attestation): Response
    {
        $viewer = $request->user();

        abort_unless(
            $viewer->id === $attestation->stagiaire_id || $viewer->isDirecteur() || $viewer->isSurveillant(),
            403
        );
        abort_unless($attestation->isApproved(), 403);

        $attestation->load('stagiaire.group.filiere', 'reviewer');

        $html = view('attestations.pdf', [
            'attestation' => $attestation,
            'logoDataUri' => $this->imageDataUri(public_path('logo/ofppt-logo.svg')),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="attestation-'.$attestation->id.'.pdf"',
        ]);
    }

    private function authorizeReview(Request $request): void
    {
        abort_unless($request->user()->isDirecteur() || $request->user()->isSurveillant(), 403);
    }

    private function imageDataUri(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = str_ends_with($path, '.svg') ? 'image/svg+xml' : 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
