<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use chillerlan\QRCode\QRCode;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class BadgeController extends Controller
{
    public function show(Request $request): View
    {
        $stagiaire = $request->user()->load('group.filiere');
        abort_unless($stagiaire->isStagiaire(), 403);

        $stagiaire->ensureBadgeCredentials();

        return view('badges.show', [
            'stagiaire' => $stagiaire->fresh('group.filiere'),
            'qrDataUri' => $this->qrDataUri($stagiaire->qr_login_token),
        ]);
    }

    public function download(Request $request): Response
    {
        $stagiaire = $request->user()->load('group.filiere');
        abort_unless($stagiaire->isStagiaire(), 403);

        $stagiaire->ensureBadgeCredentials();
        $stagiaire = $stagiaire->fresh('group.filiere');

        $html = view('badges.pdf', [
            'stagiaire' => $stagiaire,
            'qrDataUri' => $this->qrDataUri($stagiaire->qr_login_token),
            'logoDataUri' => $this->imageDataUri(public_path('logo/ofppt-logo.svg')),
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="badge-'.$stagiaire->id.'.pdf"',
        ]);
    }

    private function qrDataUri(string $token): string
    {
        return (new QRCode())->render(route('auth.qr-login', $token));
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
