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

            Attendance::updateOrCreate(
                ['timetable_session_id' => $session->id, 'stagiaire_id' => $studentId],
                [
                    'status' => $status,
                    'method' => 'manual',
                    'marked_by' => auth()->id(),
                    'marked_at' => now(),
                ]
            );

            if ($student = User::find($studentId)) {
                $riskScores->updateFor($student);
            }
        }

        return back()->with('status', 'Attendance saved.');
    }

    public function generateQr(TimetableSession $session): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        $qr = QrAttendanceSession::create([
            'timetable_session_id' => $session->id,
            'group_id' => $session->group_id,
            'secure_token' => Str::random(64),
            'short_code' => $this->uniqueShortCode(),
            'expires_at' => now()->addMinutes(config('smartcampus.qr_expires_minutes')),
            'created_by' => auth()->id(),
        ]);

        return back()->with('status', "QR/code generated. Code: {$qr->short_code}");
    }

    public function checkIn(): View
    {
        return view('attendance.check-in');
    }

    public function scan(string $token, Request $request, AttendanceSecurityService $security, RiskScoreService $riskScores): RedirectResponse
    {
        $qr = QrAttendanceSession::where('secure_token', $token)->firstOrFail();

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

        if (!$security->isAllowedIp($request->ip())) {
            $this->recordAttempt($user, $session, $request, 'ip_not_allowed');
            $this->notifyAdminsOfSuspiciousAttempt($user, $session);
            $riskScores->updateFor($user);

            return redirect()->route('attendance.check-in')->withErrors(['code' => 'Attendance rejected: you are outside the allowed campus network.']);
        }

        if (Attendance::where('timetable_session_id', $session->id)->where('stagiaire_id', $user->id)->exists()) {
            return redirect()->route('attendance.check-in')->withErrors(['code' => 'You already checked in for this session.']);
        }

        Attendance::create([
            'timetable_session_id' => $session->id,
            'stagiaire_id' => $user->id,
            'status' => 'present',
            'method' => $method,
            'marked_by' => $user->id,
            'marked_at' => now(),
        ]);

        $riskScores->updateFor($user);

        return redirect()->route('stagiaire.dashboard')->with('status', 'Attendance confirmed.');
    }

    private function recordAttempt(User $user, TimetableSession $session, Request $request, string $reason): void
    {
        AttendanceAttempt::create([
            'stagiaire_id' => $user->id,
            'timetable_session_id' => $session->id,
            'ip_address' => $request->ip(),
            'reason' => $reason,
            'metadata' => ['user_agent' => $request->userAgent()],
            'created_at' => now(),
        ]);
    }

    private function notifyAdminsOfSuspiciousAttempt(User $user, TimetableSession $session): void
    {
        User::whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])->get()
            ->each(fn (User $admin) => $admin->notify(new SmartCampusNotification(
                'Suspicious attendance attempt',
                "{$user->name} attempted attendance outside the allowed network for {$session->module->name}.",
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
