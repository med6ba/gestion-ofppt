<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\QrAttendanceSession;
use App\Models\RiskScore;
use App\Models\TimetableSession;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\AttendanceSecurityService;
use App\Services\RiskScoreService;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.index', [
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'activeQrSession'])
                ->where('formateur_id', auth()->id())
                ->forDate(now())
                ->orderBy('starts_at')
                ->get(),
            'recentSessions' => TimetableSession::with(['group', 'module', 'room'])
                ->where('formateur_id', auth()->id())
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }

    public function show(TimetableSession $session): View
    {
        $this->authorize('markAttendance', $session);

        $session->load(['group.stagiaires.riskScore', 'module', 'room', 'attendances']);
        $qr = $session->activeQrSession;
        $qrDataUri = null;

        if ($qr) {
            $qrDataUri = (new QRCode())->render(route('attendance.scan', $qr->secure_token));
        }

        return view('attendance.show', [
            'session' => $session,
            'students' => $session->group->stagiaires()->approved()->orderBy('name')->get(),
            'attendanceByStudent' => $session->attendances->keyBy('stagiaire_id'),
            'attendanceSummary' => $this->attendanceSummary($session),
            'qrSession' => $qr,
            'qrDataUri' => $qrDataUri,
            'allowedNetworks' => config('smartcampus.allowed_ip_ranges', []),
        ]);
    }

    public function storeManual(StoreManualAttendanceRequest $request, TimetableSession $session, RiskScoreService $riskScores): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        $validStudents = $session->group->stagiaires()->pluck('id')->all();

        foreach ($request->validated('attendance') as $studentId => $status) {
            if (!in_array((int) $studentId, $validStudents, true)) {
                continue;
            }

            $attendance = Attendance::updateOrCreate(
                ['timetable_session_id' => $session->id, 'stagiaire_id' => $studentId],
                [
                    'status' => $status,
                    'method' => 'manual',
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ]
            );

            // Dispatch WebSocket event
            \App\Events\AttendanceMarked::dispatch($session->id, (int) $studentId, $status);

            if ($student = User::find($studentId)) {
                $riskScores->updateFor($student);
            }
        }

        return back()->with('status', 'Attendance saved.');
    }

    public function generateQr(TimetableSession $session): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        // Delete any existing QR session for this timetable session
        QrAttendanceSession::where('timetable_session_id', $session->id)->delete();

        $qr = QrAttendanceSession::create([
            'timetable_session_id' => $session->id,
            'group_id' => $session->group_id,
            'secure_token' => Str::random(64),
            'short_code' => $this->uniqueShortCode(),
            'expires_at' => now()->addMinutes(config('smartcampus.qr_expires_minutes')),
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', "QR attendance started.");
    }

    public function refreshQr(TimetableSession $session)
    {
        $this->authorize('markAttendance', $session);

        $qr = $session->activeQrSession;
        if (!$qr) {
            return response()->json(['error' => 'No active QR session'], 404);
        }

        // Regenerate token to prevent sharing
        $qr->update([
            'secure_token' => Str::random(64),
            // Optionally refresh expiration time: 'expires_at' => now()->addMinutes(config('smartcampus.qr_expires_minutes')),
        ]);

        $qrDataUri = (new QRCode())->render(route('attendance.scan', $qr->secure_token));

        $markedStudentIds = $session->attendances()->pluck('stagiaire_id')->toArray();
        $attendances = Attendance::where('timetable_session_id', $session->id)->get(['stagiaire_id', 'status']);
        $attempts = AttendanceAttempt::where('timetable_session_id', $session->id)
            ->where('reason', 'device_already_used')
            ->whereNotIn('stagiaire_id', $markedStudentIds)
            ->with('stagiaire:id,name')
            ->latest()
            ->get();

        return response()->json([
            'qrDataUri' => $qrDataUri,
            'attendances' => $attendances,
            'attempts' => $attempts,
        ]);
    }

    public function stopQr(TimetableSession $session): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        QrAttendanceSession::where('timetable_session_id', $session->id)->delete();

        return back()->with('status', 'QR attendance stopped.');
    }

    public function checkIn(): View
    {
        return view('attendance.check-in');
    }

    public function scan(string $token, Request $request, AttendanceSecurityService $security, RiskScoreService $riskScores): RedirectResponse
    {
        $qr = QrAttendanceSession::where('secure_token', $token)->first();

        if (!$qr) {
            return redirect()->route('login')->withErrors(['email' => 'Le QR code a expiré (il change toutes les 5 secondes). Veuillez scanner le nouveau code affiché à l\'écran.']);
        }

        return $this->attemptCheckIn($qr, 'qr', $request, $security, $riskScores);
    }

    public function storeCode(Request $request, AttendanceSecurityService $security, RiskScoreService $riskScores): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $qr = QrAttendanceSession::where('short_code', Str::upper($data['code']))->latest()->first();

        if (!$qr) {
            return back()->withErrors(['code' => 'Invalid attendance code.']);
        }

        return $this->attemptCheckIn($qr, 'code', $request, $security, $riskScores);
    }

    public function reports(RiskScoreService $riskScores): View
    {
        $riskScores->refreshAll();

        return view('attendance.reports', [
            'recentAttendances' => Attendance::with(['stagiaire.group', 'session.module', 'session.room'])->latest('marked_at')->paginate(20),
            'attempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(25)->get(),
            'riskScores' => RiskScore::with('stagiaire.group')->orderByDesc('score')->take(20)->get(),
            'mostAbsentStudents' => $this->mostAbsentStudents(),
            'attendanceSummary' => $this->globalAttendanceSummary(),
        ]);
    }

    private function attemptCheckIn(QrAttendanceSession $qr, string $method, Request $request, AttendanceSecurityService $security, RiskScoreService $riskScores): RedirectResponse
    {
        $user = $request->user();
        $session = $qr->session;

        if (!$user->isStagiaire() || !$user->isApproved()) {
            abort(403);
        }

        if ($qr->isExpired()) {
            return redirect()->route('attendance.check-in')->withErrors(['code' => 'This attendance code has expired.']);
        }

        if ($user->group_id !== $qr->group_id || $user->group_id !== $session->group_id) {
            $this->recordAttempt($user, $session, $request, 'wrong_group');

            return redirect()->route('attendance.check-in')->withErrors(['code' => 'This attendance session is not for your group.']);
        }

        $deviceToken = $request->cookie('device_token');

        if (!$deviceToken) {
            $deviceToken = Str::uuid()->toString();
            cookie()->queue('device_token', $deviceToken, 60 * 24 * 365 * 10); // 10 years
        }

        if (empty($user->device_id)) {
            // Vérifier si cet appareil est déjà utilisé par un autre étudiant
            $deviceAlreadyUsed = User::where('device_id', $deviceToken)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($deviceAlreadyUsed) {
                $attempt = $this->recordAttempt($user, $session, $request, 'device_already_used');
                $this->notifyAdminsOfSuspiciousAttempt($user, $session, 'wrong_device');
                $riskScores->updateFor($user);

                // Dispatch WebSocket event
                \App\Events\DeviceConflictDetected::dispatch($session->id, $attempt->id, $user->name, $user->id);

                return redirect()->route('stagiaire.dashboard')->with('status', 'Votre scan est en attente. Veuillez voir votre formateur pour validation (Conflit d\'appareil).');
            }

            $user->update(['device_id' => $deviceToken]);
        } elseif ($user->device_id !== $deviceToken) {
            $this->recordAttempt($user, $session, $request, 'wrong_device');
            $this->notifyAdminsOfSuspiciousAttempt($user, $session, 'wrong_device');
            $riskScores->updateFor($user);

            return redirect()->route('attendance.check-in')->withErrors(['code' => 'Présence refusée : Vous devez utiliser votre appareil enregistré.']);
        }

        if (Attendance::where('timetable_session_id', $session->id)->where('stagiaire_id', $user->id)->exists()) {
            return redirect()->route('attendance.check-in')->withErrors(['code' => 'You already checked in for this session.']);
        }

        $session->attendances()->updateOrCreate(
            ['stagiaire_id' => $user->id],
            [
                'status' => 'present',
                'method' => 'qr',
                'marked_by' => null,
                'marked_at' => now(),
            ]
        );

        // Dispatch WebSocket event
        \App\Events\AttendanceMarked::dispatch($session->id, $user->id, 'present');

        $riskScores->updateFor($user);

        return redirect()->route('stagiaire.dashboard')->with('status', 'Présence marquée avec succès via QR Code.');
    }

    private function recordAttempt(User $user, TimetableSession $session, Request $request, string $reason): AttendanceAttempt
    {
        return AttendanceAttempt::create([
            'stagiaire_id' => $user->id,
            'timetable_session_id' => $session->id,
            'ip_address' => $request->ip(),
            'reason' => $reason,
            'metadata' => ['user_agent' => $request->userAgent()],
            'created_at' => now(),
        ]);
    }

    private function notifyAdminsOfSuspiciousAttempt(User $user, TimetableSession $session, string $reason = 'network'): void
    {
        $message = $reason === 'wrong_device'
            ? "{$user->name} a tenté de marquer sa présence avec un appareil non autorisé pour {$session->module->name}."
            : "{$user->name} a tenté de marquer sa présence en dehors du réseau autorisé pour {$session->module->name}.";

        User::whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])->get()
            ->each(fn (User $admin) => $admin->notify(new SmartCampusNotification(
                'Tentative de présence suspecte',
                $message,
                route('attendance.reports'),
                'security'
            )));
    }

    private function uniqueShortCode(): string
    {
        do {
            $code = Str::upper(Str::random(5));
        } while (QrAttendanceSession::where('short_code', $code)->exists());

        return $code;
    }

    private function attendanceSummary(TimetableSession $session): array
    {
        $counts = $session->attendances->countBy('status');
        $totalStudents = max(1, $session->group->stagiaires()->approved()->count());
        $presentLike = (int) $counts->get('present', 0) + (int) $counts->get('late', 0) + (int) $counts->get('justified', 0);

        return [
            'present' => (int) $counts->get('present', 0),
            'absent' => (int) $counts->get('absent', 0),
            'late' => (int) $counts->get('late', 0),
            'justified' => (int) $counts->get('justified', 0),
            'marked' => $session->attendances->count(),
            'missing' => max(0, $totalStudents - $session->attendances->count()),
            'attendanceRate' => round(($presentLike / $totalStudents) * 100, 1),
        ];
    }

    private function globalAttendanceSummary(): array
    {
        $counts = Attendance::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max(1, $counts->sum());
        $presentLike = (int) $counts->get('present', 0) + (int) $counts->get('late', 0) + (int) $counts->get('justified', 0);

        return [
            'present' => (int) $counts->get('present', 0),
            'absent' => (int) $counts->get('absent', 0),
            'late' => (int) $counts->get('late', 0),
            'justified' => (int) $counts->get('justified', 0),
            'attendanceRate' => round(($presentLike / $total) * 100, 1),
        ];
    }

    private function mostAbsentStudents(int $limit = 8)
    {
        return User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->with(['group', 'riskScore'])
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query->where('status', 'absent'),
                'attendances as late_count' => fn ($query) => $query->where('status', 'late'),
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->take($limit)
            ->get();
    }
}
