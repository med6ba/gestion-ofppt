<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\AbsenceAuthorizationRequest;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\SafeNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AbsenceAuthorizationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $stagiaire = $request->user()->load('group.filiere');
        abort_unless($stagiaire->isStagiaire(), 403);

        return view('absences.index', [
            'requests' => $stagiaire->absenceAuthorizationRequests()->latest()->paginate(10),
            'stagiaire' => $stagiaire,
        ]);
    }

    public function store(Request $request, SafeNotificationService $notifier): RedirectResponse
    {
        $stagiaire = $request->user();
        abort_unless($stagiaire->isStagiaire(), 403);

        $data = $request->validate([
            'absence_date' => ['required', 'date', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'reason' => ['required', 'string', 'min:8', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $path = $request->file('attachment')?->store('absence-attachments');

        AbsenceAuthorizationRequest::create([
            'stagiaire_id' => $stagiaire->id,
            'absence_date' => $data['absence_date'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'reason' => $data['reason'],
            'attachment_path' => $path,
        ]);

        User::query()
            ->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])
            ->get()
            ->each(fn (User $admin) => $notifier->send($admin, new SmartCampusNotification(
                __('messages.mail.absence_admin_title'),
                __('messages.mail.absence_admin_body', ['name' => $stagiaire->name, 'date' => $data['absence_date']]),
                route('absences.manage'),
                'documents'
            )));

        return back()->with('status', __('messages.absences.sent'));
    }

    public function manage(): View
    {
        return view('absences.manage', [
            'requests' => AbsenceAuthorizationRequest::with(['stagiaire.group.filiere', 'reviewer'])
                ->latest()
                ->paginate(20),
            'pendingCount' => AbsenceAuthorizationRequest::where('status', AbsenceAuthorizationRequest::STATUS_PENDING)->count(),
        ]);
    }

    public function approve(Request $request, AbsenceAuthorizationRequest $absence, SafeNotificationService $notifier): RedirectResponse
    {
        $this->authorizeReview($request);

        $data = $request->validate([
            'surveillant_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $absence->update([
            'status' => AbsenceAuthorizationRequest::STATUS_APPROVED,
            'surveillant_note' => $data['surveillant_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $notifier->send($absence->stagiaire, new SmartCampusNotification(
            __('messages.mail.absence_approved_title'),
            __('messages.mail.absence_approved_body', ['date' => $absence->absence_date->format('Y-m-d')]),
            route('absences.index'),
            'documents',
            sendMail: true
        ));

        return back()->with('status', __('messages.absences.approved'));
    }

    public function reject(Request $request, AbsenceAuthorizationRequest $absence, SafeNotificationService $notifier): RedirectResponse
    {
        $this->authorizeReview($request);

        $data = $request->validate([
            'surveillant_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $absence->update([
            'status' => AbsenceAuthorizationRequest::STATUS_REJECTED,
            'surveillant_note' => $data['surveillant_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $notifier->send($absence->stagiaire, new SmartCampusNotification(
            __('messages.mail.absence_rejected_title'),
            __('messages.mail.absence_rejected_body', ['date' => $absence->absence_date->format('Y-m-d')]).($absence->surveillant_note ? ' '.__('messages.common.note').': '.$absence->surveillant_note : ''),
            route('absences.index'),
            'documents',
            sendMail: true
        ));

        return back()->with('status', __('messages.absences.rejected'));
    }

    public function attachment(Request $request, AbsenceAuthorizationRequest $absence)
    {
        $viewer = $request->user();

        abort_unless(
            $viewer->id === $absence->stagiaire_id || $viewer->isDirecteur() || $viewer->isSurveillant(),
            403
        );
        abort_if(blank($absence->attachment_path) || !Storage::exists($absence->attachment_path), 404);

        return Storage::download($absence->attachment_path);
    }

    private function authorizeReview(Request $request): void
    {
        abort_unless($request->user()->isDirecteur() || $request->user()->isSurveillant(), 403);
    }
}
