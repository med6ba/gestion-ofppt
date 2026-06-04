<?php

namespace App\Http\Controllers\SmartCampus;

use App\Events\AttendanceMarked;
use App\Events\DeviceConflictDetected;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreManualAttendanceRequest;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceSession;
use App\Models\AttendanceSetting;
use App\Models\QrAttendanceSession;
use App\Models\RiskScore;
use App\Models\StudentPresenceProfile;
use App\Models\TimetableSession;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\AttendanceSecurityService;
use App\Services\PresenceXpService;
use App\Services\RiskScoreService;
use chillerlan\QRCode\QRCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(): View
    {
        return view('attendance.index', [
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'activeQrSession', 'activeAttendanceSession'])
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

        $attendanceSession = $this->latestAttendanceSession($session);
        $attendanceRecords = $attendanceSession
            ? $attendanceSession->attendances()->with(['stagiaire.riskScore', 'stagiaire.presenceProfile'])->get()
            : $session->attendances()->with(['stagiaire.riskScore', 'stagiaire.presenceProfile'])->get();

        $session->load(['group.stagiaires.riskScore', 'group.stagiaires.presenceProfile', 'module', 'room']);

        $qr = $attendanceSession?->activeQrToken ?? $session->activeQrSession;
        $qrDataUri = $qr ? (new QRCode())->render(route('attendance.scan', $qr->secure_token)) : null;

        return view('attendance.show', [
            'session' => $session,
            'attendanceSession' => $attendanceSession,
            'students' => $session->group->stagiaires()->approved()->with(['riskScore', 'presenceProfile'])->orderBy('name')->get(),
            'attendanceByStudent' => $attendanceRecords->keyBy('stagiaire_id'),
            'attendanceSummary' => $this->attendanceSummary($session, $attendanceRecords),
            'latePending' => $attendanceRecords->where('status', Attendance::STATUS_LATE_PENDING),
            'severeLatePending' => $attendanceRecords->where('status', Attendance::STATUS_SEVERE_LATE_PENDING),
            'qrSession' => $qr,
            'qrDataUri' => $qrDataUri,
            'allowedNetworks' => config('smartcampus.allowed_ip_ranges', []),
            'auditLogs' => $attendanceSession
                ? AttendanceAuditLog::with(['stagiaire', 'changedBy'])
                    ->where('attendance_session_id', $attendanceSession->id)
                    ->latest('created_at')
                    ->take(12)
                    ->get()
                : collect(),
        ]);
    }

    public function storeManual(
        StoreManualAttendanceRequest $request,
        TimetableSession $session,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);

        if ($this->latestAttendanceSession($session)?->status === AttendanceSession::STATUS_CLOSED) {
            return back()->withErrors(['attendance' => 'Cette seance est deja cloturee. Les statuts manuels ne peuvent plus etre modifies.']);
        }

        $attendanceSession = $this->ensureAttendanceSession($session, $request->user());
        $validStudents = $session->group->stagiaires()->approved()->pluck('id')->all();

        foreach ($request->validated('attendance') as $studentId => $status) {
            if (!in_array((int) $studentId, $validStudents, true)) {
                continue;
            }

            $attendance = $this->findAttendanceRecord($attendanceSession, (int) $studentId);
            $oldStatus = $attendance?->status;

            $attendance = Attendance::query()->updateOrCreate(
                ['timetable_session_id' => $session->id, 'stagiaire_id' => $studentId],
                [
                    'attendance_session_id' => $attendanceSession->id,
                    'status' => $status,
                    'method' => Attendance::METHOD_MANUAL,
                    'marked_by' => $request->user()->id,
                    'marked_at' => now(),
                    'check_in_at' => now(),
                    'delay_minutes' => $attendanceSession->delayMinutes(),
                    'validated_by' => in_array($status, [Attendance::STATUS_LATE_VALIDATED, Attendance::STATUS_SEVERE_LATE_VALIDATED], true)
                        ? $request->user()->id
                        : null,
                    'validated_at' => in_array($status, [Attendance::STATUS_LATE_VALIDATED, Attendance::STATUS_SEVERE_LATE_VALIDATED], true)
                        ? now()
                        : null,
                    'rejection_reason' => null,
                ]
            );

            if ($oldStatus !== $status) {
                $this->auditAttendance($attendance, $oldStatus, $status, $request->user(), 'Saisie manuelle formateur');
            }

            $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);
        }

        return back()->with('status', 'Attendance saved.');
    }

    public function generateQr(TimetableSession $session, Request $request): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        if ($this->latestAttendanceSession($session)?->status === AttendanceSession::STATUS_CLOSED) {
            return back()->withErrors(['qr' => 'Cette seance est deja cloturee.']);
        }

        $attendanceSession = $this->currentAttendanceSession($session);

        if ($attendanceSession) {
            if ($attendanceSession->status === AttendanceSession::STATUS_CLOSED) {
                return back()->withErrors(['qr' => 'Cette seance est deja cloturee.']);
            }

            if (!$attendanceSession->isQrPhaseOpen()) {
                return back()->withErrors(['qr' => 'La phase QR est terminee. Les stagiaires doivent declarer leur retard.']);
            }

            if ($attendanceSession->activeQrToken) {
                return back()->with('status', 'QR attendance is already active.');
            }

            return back()->withErrors(['qr' => 'Le QR a ete ferme pendant la phase active. Utilisez Correction erreur QR avec un motif.']);
        }

        $attendanceSession = $this->createAttendanceSession($session, $request->user());

        QrAttendanceSession::where('timetable_session_id', $session->id)->delete();

        QrAttendanceSession::create([
            'attendance_session_id' => $attendanceSession->id,
            'timetable_session_id' => $session->id,
            'group_id' => $session->group_id,
            'secure_token' => Str::random(64),
            'short_code' => $this->uniqueShortCode(),
            'expires_at' => $attendanceSession->qrClosesAt(),
            'created_by' => $request->user()->id,
        ]);

        $refreshInterval = \App\Models\Setting::get('qr_refresh_interval', 15);

        return back()->with('status', 'QR attendance started. Rotation active toutes les ' . $refreshInterval . ' secondes.');
    }

    public function refreshQr(TimetableSession $session)
    {
        $this->authorize('markAttendance', $session);

        $attendanceSession = $this->currentAttendanceSession($session);

        if (!$attendanceSession) {
            return response()->json(['error' => 'No active attendance session'], 404);
        }

        $attendanceSession->refreshClockStatus();

        if (!$attendanceSession->isQrPhaseOpen()) {
            QrAttendanceSession::where('attendance_session_id', $attendanceSession->id)->delete();

            return response()->json([
                'qrClosed' => true,
                'message' => 'QR phase closed',
                'attendances' => $this->attendancePayload($attendanceSession),
            ], 410);
        }

        $qr = $attendanceSession->activeQrToken;

        if (!$qr) {
            return response()->json(['error' => 'QR was stopped. Use QR mistake correction.'], 404);
        }

        $qr->update([
            'secure_token' => Str::random(64),
            'short_code' => $this->uniqueShortCode(),
            'expires_at' => $attendanceSession->qrClosesAt(),
        ]);

        $markedStudentIds = $attendanceSession->attendances()->pluck('stagiaire_id')->toArray();
        $attempts = AttendanceAttempt::where('timetable_session_id', $session->id)
            ->where('reason', 'device_already_used')
            ->whereNotIn('stagiaire_id', $markedStudentIds)
            ->with('stagiaire:id,name')
            ->latest()
            ->get();

        return response()->json([
            'qrDataUri' => (new QRCode())->render(route('attendance.scan', $qr->secure_token)),
            'qrClosesAt' => $attendanceSession->qrClosesAt()->toIso8601String(),
            'serverNow' => now()->toIso8601String(),
            'secondsRemaining' => (int) max(0, now()->diffInSeconds($attendanceSession->qrClosesAt(), false)),
            'attendances' => $this->attendancePayload($attendanceSession),
            'attempts' => $attempts,
        ]);
    }

    public function stopQr(TimetableSession $session): RedirectResponse
    {
        $this->authorize('markAttendance', $session);

        $attendanceSession = $this->currentAttendanceSession($session);
        QrAttendanceSession::where('timetable_session_id', $session->id)
            ->when($attendanceSession, fn ($query) => $query->where('attendance_session_id', $attendanceSession->id))
            ->delete();

        return back()->with('status', 'QR masque. Si des stagiaires ont ete bloques par erreur, utilisez Correction erreur QR avec motif.');
    }

    public function mine(PresenceXpService $presenceXp): View
    {
        $user = auth()->user();
        abort_unless($user->isStagiaire(), 403);

        $profile = $presenceXp->refreshFor($user);
        $attendances = Attendance::with(['session.module', 'session.room', 'session.formateur'])
            ->where('stagiaire_id', $user->id)
            ->latest('marked_at')
            ->paginate(15);

        $counts = Attendance::where('stagiaire_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('attendance.mine', [
            'attendances' => $attendances,
            'presenceProfile' => $profile,
            'summary' => [
                'present' => (int) $counts->get(Attendance::STATUS_PRESENT, 0),
                'acceptedLate' => (int) $counts->get(Attendance::STATUS_LATE_VALIDATED, 0) + (int) $counts->get(Attendance::STATUS_SEVERE_LATE_VALIDATED, 0),
                'pendingLate' => (int) $counts->get(Attendance::STATUS_LATE_PENDING, 0) + (int) $counts->get(Attendance::STATUS_SEVERE_LATE_PENDING, 0),
                'absent' => (int) $counts->get(Attendance::STATUS_ABSENT, 0),
                'justified' => (int) $counts->get(Attendance::STATUS_JUSTIFIED, 0),
            ],
        ]);
    }

    public function checkIn(): View
    {
        return view('attendance.check-in');
    }

    public function scan(
        string $token,
        Request $request,
        AttendanceSecurityService $security,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $qr = QrAttendanceSession::where('secure_token', $token)->first();

        if (!$qr) {
            $refreshInterval = \App\Models\Setting::get('qr_refresh_interval', 15);
            return redirect()->route('login')->withErrors(['email' => 'Le QR code a expire (il change toutes les ' . $refreshInterval . ' secondes). Veuillez scanner le nouveau code affiche.']);
        }

        return $this->attemptCheckIn($qr, Attendance::METHOD_QR, $request, $security, $riskScores, $presenceXp);
    }

    public function storeCode(
        Request $request,
        AttendanceSecurityService $security,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:12'],
        ]);

        $qr = QrAttendanceSession::where('short_code', Str::upper($data['code']))->latest()->first();

        if (!$qr) {
            return back()->withErrors(['code' => 'Invalid attendance code.']);
        }

        return $this->attemptCheckIn($qr, Attendance::METHOD_CODE, $request, $security, $riskScores, $presenceXp);
    }

    public function declareLate(Request $request, RiskScoreService $riskScores, PresenceXpService $presenceXp): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->isStagiaire() && $user->isApproved(), 403);

        $data = $request->validate([
            'attendance_session_id' => ['required', 'integer', 'exists:attendance_sessions,id'],
        ]);

        $attendanceSession = AttendanceSession::with(['timetableSession.group', 'timetableSession.module', 'formateur'])
            ->findOrFail($data['attendance_session_id']);
        $attendanceSession->refreshClockStatus();
        $timetableSession = $attendanceSession->timetableSession;

        abort_unless($user->group_id === $timetableSession->group_id, 403);

        if (!$attendanceSession->isLateDeclarationOpen()) {
            if (now()->gt($attendanceSession->severeLateClosesAt())) {
                return back()->withErrors(['late' => 'La limite de retard est depassee. Votre cas doit etre traite par l’administration.']);
            }

            return back()->withErrors(['late' => 'La declaration de retard est disponible uniquement apres la phase QR.']);
        }

        if ($this->findAttendanceRecord($attendanceSession, $user->id)) {
            return back()->withErrors(['late' => 'Vous avez deja une presence ou une declaration pour cette seance.']);
        }

        $delayMinutes = $attendanceSession->delayMinutes();
        $status = $delayMinutes <= $attendanceSession->normal_late_until_minutes
            ? Attendance::STATUS_LATE_PENDING
            : Attendance::STATUS_SEVERE_LATE_PENDING;

        $attendance = Attendance::create([
            'attendance_session_id' => $attendanceSession->id,
            'timetable_session_id' => $timetableSession->id,
            'stagiaire_id' => $user->id,
            'status' => $status,
            'method' => Attendance::METHOD_LATE_DECLARATION,
            'marked_by' => null,
            'marked_at' => now(),
            'check_in_at' => now(),
            'delay_minutes' => $delayMinutes,
        ]);

        $message = $status === Attendance::STATUS_LATE_PENDING
            ? 'Retard declare. En attente de validation par le formateur.'
            : 'Retard important declare. Votre cas doit etre valide par le Surveillant General.';

        if ($status === Attendance::STATUS_LATE_PENDING || $status === Attendance::STATUS_SEVERE_LATE_PENDING) {
            broadcast(new \App\Events\LateRequestCreated($attendance))->toOthers();
        }

        $user->notify(new SmartCampusNotification('Declaration de retard envoyee', $message, route('stagiaire.dashboard'), 'attendance'));

        if ($status === Attendance::STATUS_LATE_PENDING) {
            $attendanceSession->formateur->notify(new SmartCampusNotification(
                'Nouveau retard a valider',
                "{$user->name} a declare {$delayMinutes} min de retard pour {$timetableSession->module->name}.",
                route('attendance.show', $timetableSession),
                'attendance'
            ));
        } else {
            $attendanceSession->formateur->notify(new SmartCampusNotification(
                'Retard important transfere',
                "{$user->name} doit etre valide par le Surveillant General.",
                route('attendance.show', $timetableSession),
                'attendance'
            ));

            User::role(User::ROLE_SURVEILLANT)->get()
                ->each(fn (User $surveillant) => $surveillant->notify(new SmartCampusNotification(
                    'Retard important a verifier',
                    "{$user->name} a declare {$delayMinutes} min de retard.",
                    route('surveillant.dashboard'),
                    'attendance'
                )));
        }

        $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);

        return redirect()->route('stagiaire.dashboard')->with('status', $message);
    }

    public function validateLate(
        TimetableSession $session,
        Attendance $attendance,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);
        $this->assertAttendanceBelongsToSession($attendance, $session);

        if ($attendance->status !== Attendance::STATUS_LATE_PENDING) {
            return back()->withErrors(['late' => 'Seuls les retards normaux en attente peuvent etre valides par le formateur.']);
        }

        $this->resolveAttendance(
            $attendance,
            Attendance::STATUS_LATE_VALIDATED,
            $request->user(),
            'Validation formateur',
            null,
            $riskScores,
            $presenceXp
        );

        return back()->with('status', 'Retard valide.');
    }

    public function rejectLate(
        TimetableSession $session,
        Attendance $attendance,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);
        $this->assertAttendanceBelongsToSession($attendance, $session);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($attendance->status !== Attendance::STATUS_LATE_PENDING) {
            return back()->withErrors(['late' => 'Seuls les retards normaux en attente peuvent etre refuses par le formateur.']);
        }

        $this->resolveAttendance(
            $attendance,
            Attendance::STATUS_LATE_REJECTED,
            $request->user(),
            $data['rejection_reason'],
            $data['rejection_reason'],
            $riskScores,
            $presenceXp
        );

        return back()->with('status', 'Retard refuse.');
    }

    public function bulkValidateLate(
        TimetableSession $session,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);

        $data = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['integer'],
        ]);

        $attendances = Attendance::whereIn('id', $data['attendance_ids'])
            ->where('timetable_session_id', $session->id)
            ->where('status', Attendance::STATUS_LATE_PENDING)
            ->get();

        foreach ($attendances as $attendance) {
            $this->resolveAttendance($attendance, Attendance::STATUS_LATE_VALIDATED, $request->user(), 'Validation groupée formateur', null, $riskScores, $presenceXp);
        }

        return back()->with('status', $attendances->count().' retards valides.');
    }

    public function bulkRejectLate(
        TimetableSession $session,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);

        $data = $request->validate([
            'attendance_ids' => ['required', 'array', 'min:1'],
            'attendance_ids.*' => ['integer'],
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $attendances = Attendance::whereIn('id', $data['attendance_ids'])
            ->where('timetable_session_id', $session->id)
            ->where('status', Attendance::STATUS_LATE_PENDING)
            ->get();

        foreach ($attendances as $attendance) {
            $this->resolveAttendance($attendance, Attendance::STATUS_LATE_REJECTED, $request->user(), $data['rejection_reason'], $data['rejection_reason'], $riskScores, $presenceXp);
        }

        return back()->with('status', $attendances->count().' retards refuses.');
    }

    public function validateSevereLate(Attendance $attendance, Request $request, RiskScoreService $riskScores, PresenceXpService $presenceXp): RedirectResponse
    {
        abort_unless($request->user()->isSurveillant(), 403);

        if ($attendance->status !== Attendance::STATUS_SEVERE_LATE_PENDING) {
            return back()->withErrors(['late' => 'Ce retard important n’est plus en attente.']);
        }

        $this->resolveAttendance(
            $attendance,
            Attendance::STATUS_SEVERE_LATE_VALIDATED,
            $request->user(),
            'Validation Surveillant General',
            null,
            $riskScores,
            $presenceXp
        );

        return back()->with('status', 'Retard important valide.');
    }

    public function rejectSevereLate(Attendance $attendance, Request $request, RiskScoreService $riskScores, PresenceXpService $presenceXp): RedirectResponse
    {
        abort_unless($request->user()->isSurveillant(), 403);

        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        if ($attendance->status !== Attendance::STATUS_SEVERE_LATE_PENDING) {
            return back()->withErrors(['late' => 'Ce retard important n’est plus en attente.']);
        }

        $this->resolveAttendance(
            $attendance,
            Attendance::STATUS_SEVERE_LATE_REJECTED,
            $request->user(),
            $data['rejection_reason'],
            $data['rejection_reason'],
            $riskScores,
            $presenceXp
        );

        return back()->with('status', 'Retard important refuse.');
    }

    public function correctQrMistake(
        TimetableSession $session,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);

        $data = $request->validate([
            'student_ids' => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer'],
            'correction_type' => ['required', 'in:present,late_validated'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $attendanceSession = $this->currentAttendanceSession($session);

        if (!$attendanceSession) {
            if ($this->latestAttendanceSession($session)?->status === AttendanceSession::STATUS_CLOSED) {
                return back()->withErrors(['correction' => 'Cette seance est deja cloturee.']);
            }

            return back()->withErrors(['correction' => 'Aucune session d’appel active a corriger.']);
        }

        $validStudents = $session->group->stagiaires()->approved()->pluck('id')->all();
        $changed = 0;

        foreach ($data['student_ids'] as $studentId) {
            if (!in_array((int) $studentId, $validStudents, true)) {
                continue;
            }

            $attendance = $this->findAttendanceRecord($attendanceSession, (int) $studentId);
            $oldStatus = $attendance?->status;

            $attendance = Attendance::query()->updateOrCreate(
                ['timetable_session_id' => $session->id, 'stagiaire_id' => $studentId],
                [
                    'attendance_session_id' => $attendanceSession->id,
                    'status' => $data['correction_type'],
                    'method' => Attendance::METHOD_QR_CORRECTION,
                    'marked_by' => $request->user()->id,
                    'marked_at' => now(),
                    'check_in_at' => now(),
                    'delay_minutes' => $attendanceSession->delayMinutes(),
                    'validated_by' => $data['correction_type'] === Attendance::STATUS_LATE_VALIDATED ? $request->user()->id : null,
                    'validated_at' => $data['correction_type'] === Attendance::STATUS_LATE_VALIDATED ? now() : null,
                    'rejection_reason' => null,
                ]
            );

            $this->auditAttendance($attendance, $oldStatus, $data['correction_type'], $request->user(), $data['reason']);
            $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);
            $changed++;
        }

        return back()->with('status', "{$changed} correction(s) enregistree(s) et auditee(s).");
    }

    public function finalizeSession(
        TimetableSession $session,
        Request $request,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $this->authorize('markAttendance', $session);

        if ($this->latestAttendanceSession($session)?->status === AttendanceSession::STATUS_CLOSED) {
            return back()->with('status', 'Cette seance est deja cloturee.');
        }

        $attendanceSession = $this->currentAttendanceSession($session);

        if (!$attendanceSession) {
            return back()->withErrors(['finalize' => 'Demarrez d’abord l’appel pour cette seance.']);
        }

        $latePendingCount = $attendanceSession->attendances()
            ->where('status', Attendance::STATUS_LATE_PENDING)
            ->count();

        if ($latePendingCount > 0) {
            return back()->withErrors(['finalize' => 'Vous devez valider ou refuser les retards en attente avant de cloturer la seance.']);
        }

        $students = $session->group->stagiaires()->approved()->get();

        foreach ($students as $student) {
            $existingAttendance = $this->findAttendanceRecord($attendanceSession, $student->id);

            if ($existingAttendance) {
                if (!$existingAttendance->attendance_session_id) {
                    $existingAttendance->update(['attendance_session_id' => $attendanceSession->id]);
                }

                continue;
            }

            $attendance = Attendance::create([
                'attendance_session_id' => $attendanceSession->id,
                'timetable_session_id' => $session->id,
                'stagiaire_id' => $student->id,
                'status' => Attendance::STATUS_ABSENT,
                'method' => Attendance::METHOD_FINALIZATION,
                'marked_by' => $request->user()->id,
                'marked_at' => now(),
                'check_in_at' => null,
            ]);

            $this->auditAttendance($attendance, null, Attendance::STATUS_ABSENT, $request->user(), 'Cloture de seance');
            $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);
        }

        $severeLatePendingCount = $attendanceSession->attendances()
            ->where('status', Attendance::STATUS_SEVERE_LATE_PENDING)
            ->count();

        $attendanceSession->update([
            'status' => AttendanceSession::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
        QrAttendanceSession::where('attendance_session_id', $attendanceSession->id)->delete();

        broadcast(new \App\Events\AttendanceSessionClosed($attendanceSession))->toOthers();

        $message = $severeLatePendingCount > 0
            ? 'Seance cloturee. Certains retards importants restent en attente de validation par le Surveillant General.'
            : 'Seance cloturee.';

        return back()->with('status', $message);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isDirecteur() || $request->user()->isSurveillant(), 403);

        $data = $request->validate([
            'qr_phase_minutes' => ['required', 'integer', 'min:1', 'max:60'],
            'normal_late_until_minutes' => ['required', 'integer', 'min:2', 'max:180'],
            'severe_late_until_minutes' => ['required', 'integer', 'min:3', 'max:240'],
        ]);

        if ($data['normal_late_until_minutes'] <= $data['qr_phase_minutes']) {
            return back()->withErrors(['normal_late_until_minutes' => 'La limite de retard normal doit etre superieure a la phase QR.']);
        }

        if ($data['severe_late_until_minutes'] <= $data['normal_late_until_minutes']) {
            return back()->withErrors(['severe_late_until_minutes' => 'La limite de retard important doit etre superieure a la limite normale.']);
        }

        foreach ($data as $key => $value) {
            AttendanceSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (string) $value, 'updated_by' => $request->user()->id]
            );
        }

        return back()->with('status', 'Parametres de retard mis a jour.');
    }

    public function reports(RiskScoreService $riskScores, PresenceXpService $presenceXp): View
    {
        $riskScores->refreshAll();
        $presenceXp->refreshAll();

        return view('attendance.reports', [
            'recentAttendances' => Attendance::with(['stagiaire.group', 'session.module', 'session.room'])->latest('marked_at')->paginate(20),
            'attempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(25)->get(),
            'riskScores' => RiskScore::with('stagiaire.group')->orderByDesc('score')->take(20)->get(),
            'mostAbsentStudents' => $this->mostAbsentStudents(),
            'attendanceSummary' => $this->globalAttendanceSummary(),
            'attendanceSettings' => AttendanceSetting::minuteSettings(),
            'severeLateQueue' => Attendance::with(['stagiaire.group', 'session.module', 'session.formateur'])
                ->where('status', Attendance::STATUS_SEVERE_LATE_PENDING)
                ->latest('check_in_at')
                ->take(20)
                ->get(),
            'auditLogs' => AttendanceAuditLog::with(['stagiaire.group', 'changedBy'])
                ->latest('created_at')
                ->take(20)
                ->get(),
            'topProfiles' => StudentPresenceProfile::with('stagiaire.group')->orderByDesc('xp_points')->take(8)->get(),
        ]);
    }

    public function leaderboard(PresenceXpService $presenceXp): View
    {
        $presenceXp->refreshAll();
        $user = auth()->user();

        $profiles = StudentPresenceProfile::query()
            ->with('stagiaire.group')
            ->when($user->isFormateur(), function ($query) use ($user) {
                $groupIds = $user->teachingGroups()->pluck('groups.id');
                $query->whereHas('stagiaire', fn ($studentQuery) => $studentQuery->whereIn('group_id', $groupIds));
            })
            ->orderByDesc('xp_points')
            ->orderByDesc('attendance_streak')
            ->get();

        $myProfile = $user->isStagiaire()
            ? $profiles->firstWhere('stagiaire_id', $user->id) ?? $presenceXp->refreshFor($user)
            : null;

        return view('attendance.leaderboard', [
            'profiles' => $profiles,
            'myProfile' => $myProfile,
            'myRank' => $myProfile ? ($profiles->search(fn ($profile) => $profile->stagiaire_id === $user->id) + 1) : null,
            'bestGroups' => $this->bestPresenceGroups(),
            'riskProfiles' => $profiles->filter(fn ($profile) => $profile->xp_points < 0)->take(10),
        ]);
    }

    private function attemptCheckIn(
        QrAttendanceSession $qr,
        string $method,
        Request $request,
        AttendanceSecurityService $security,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): RedirectResponse {
        $user = $request->user();
        $session = $qr->session;
        $attendanceSession = $qr->attendanceSession ?? $this->currentAttendanceSession($session);

        if (!$user->isStagiaire() || !$user->isApproved()) {
            abort(403);
        }

        if (!$attendanceSession || $qr->isExpired() || !$attendanceSession->isQrPhaseOpen()) {
            return redirect()->route('attendance.check-in')->withErrors(['code' => 'La phase QR est terminee. Utilisez la declaration de retard si la fenetre est encore ouverte.']);
        }

        if ($user->group_id !== $qr->group_id || $user->group_id !== $session->group_id) {
            $this->recordAttempt($user, $session, $request, 'wrong_group');

            return redirect()->route('attendance.check-in')->withErrors(['code' => 'This attendance session is not for your group.']);
        }

        $deviceToken = $request->cookie('device_token');

        if (!$deviceToken) {
            $deviceToken = Str::uuid()->toString();
            cookie()->queue('device_token', $deviceToken, 60 * 24 * 365 * 10);
        }

        if (empty($user->device_id)) {
            $deviceAlreadyUsed = User::where('device_id', $deviceToken)
                ->where('id', '!=', $user->id)
                ->exists();

            if ($deviceAlreadyUsed) {
                $attempt = $this->recordAttempt($user, $session, $request, 'device_already_used');
                $this->notifyAdminsOfSuspiciousAttempt($user, $session, 'wrong_device');
                $riskScores->updateFor($user);

                DeviceConflictDetected::dispatch($session->id, $attempt->id, $user->name, $user->id);

                return redirect()->route('stagiaire.dashboard')->with('status', 'Votre scan est en attente. Veuillez voir votre formateur pour validation (Conflit d’appareil).');
            }

            $user->update(['device_id' => $deviceToken]);
        } elseif ($user->device_id !== $deviceToken) {
            $this->recordAttempt($user, $session, $request, 'wrong_device');
            $this->notifyAdminsOfSuspiciousAttempt($user, $session, 'wrong_device');
            $riskScores->updateFor($user);

            return redirect()->route('attendance.check-in')->withErrors(['code' => 'Presence refusee : Vous devez utiliser votre appareil enregistre.']);
        }

        if ($this->findAttendanceRecord($attendanceSession, $user->id)) {
            return redirect()->route('attendance.check-in')->withErrors(['code' => 'You already checked in for this session.']);
        }

        $attendance = Attendance::create([
            'attendance_session_id' => $attendanceSession->id,
            'timetable_session_id' => $session->id,
            'stagiaire_id' => $user->id,
            'status' => Attendance::STATUS_PRESENT,
            'method' => $method,
            'marked_by' => null,
            'marked_at' => now(),
            'check_in_at' => now(),
            'delay_minutes' => $attendanceSession->delayMinutes(),
        ]);

        $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);

        return redirect()->route('stagiaire.dashboard')->with('status', 'Presence marquee avec succes via QR Code.');
    }

    private function currentAttendanceSession(TimetableSession $session): ?AttendanceSession
    {
        $attendanceSession = $session->activeAttendanceSession()->with('activeQrToken')->first();
        $attendanceSession?->refreshClockStatus();

        return $attendanceSession?->fresh(['activeQrToken']);
    }

    private function latestAttendanceSession(TimetableSession $session): ?AttendanceSession
    {
        $attendanceSession = $session->attendanceSessions()
            ->with('activeQrToken')
            ->latest('actual_started_at')
            ->latest('id')
            ->first();

        $attendanceSession?->refreshClockStatus();

        return $attendanceSession?->fresh(['activeQrToken']);
    }

    private function createAttendanceSession(TimetableSession $session, User $formateur): AttendanceSession
    {
        $settings = AttendanceSetting::minuteSettings();

        $attendanceSession = AttendanceSession::create([
            'timetable_session_id' => $session->id,
            'formateur_id' => $formateur->id,
            'actual_started_at' => now(),
            'qr_phase_minutes' => $settings['qr_phase_minutes'],
            'normal_late_until_minutes' => $settings['normal_late_until_minutes'],
            'severe_late_until_minutes' => $settings['severe_late_until_minutes'],
            'status' => AttendanceSession::STATUS_OPEN,
        ]);

        broadcast(new \App\Events\AttendanceSessionStarted($attendanceSession))->toOthers();

        return $attendanceSession;
    }

    private function ensureAttendanceSession(TimetableSession $session, User $formateur): AttendanceSession
    {
        return $this->currentAttendanceSession($session) ?? $this->createAttendanceSession($session, $formateur);
    }

    private function findAttendanceRecord(AttendanceSession $attendanceSession, int $studentId): ?Attendance
    {
        return Attendance::query()
            ->where(function ($query) use ($attendanceSession) {
                $query->where('attendance_session_id', $attendanceSession->id)
                    ->orWhere(function ($legacyQuery) use ($attendanceSession) {
                        $legacyQuery->whereNull('attendance_session_id')
                            ->where('timetable_session_id', $attendanceSession->timetable_session_id);
                    });
            })
            ->where('stagiaire_id', $studentId)
            ->first();
    }

    private function resolveAttendance(
        Attendance $attendance,
        string $newStatus,
        User $actor,
        string $auditReason,
        ?string $rejectionReason,
        RiskScoreService $riskScores,
        PresenceXpService $presenceXp
    ): Attendance {
        $oldStatus = $attendance->status;
        $attendance->update([
            'status' => $newStatus,
            'validated_by' => $actor->id,
            'validated_at' => now(),
            'rejection_reason' => $rejectionReason,
        ]);

        $this->auditAttendance($attendance, $oldStatus, $newStatus, $actor, $auditReason);
        $this->refreshAttendanceSideEffects($attendance, $riskScores, $presenceXp);

        $student = $attendance->stagiaire;
        $student->notify(new SmartCampusNotification(
            str_contains($newStatus, 'rejected') ? 'Retard refuse' : 'Retard valide',
            "Votre statut est maintenant {$newStatus}.",
            route('stagiaire.dashboard'),
            'attendance'
        ));

        if (in_array($newStatus, [Attendance::STATUS_LATE_VALIDATED, Attendance::STATUS_LATE_REJECTED, Attendance::STATUS_SEVERE_LATE_VALIDATED, Attendance::STATUS_SEVERE_LATE_REJECTED])) {
            broadcast(new \App\Events\LateRequestReviewed($attendance))->toOthers();
        }

        return $attendance;
    }

    private function refreshAttendanceSideEffects(Attendance $attendance, RiskScoreService $riskScores, PresenceXpService $presenceXp): void
    {
        AttendanceMarked::dispatch($attendance->timetable_session_id, $attendance->stagiaire_id, $attendance->status);

        $student = $attendance->stagiaire()->first();

        if (!$student) {
            return;
        }

        $oldRiskLevel = $student->riskScore?->level;
        $risk = $riskScores->updateFor($student);
        $profile = $presenceXp->refreshFor($student);

        $student->notify(new SmartCampusNotification(
            'Podium mis a jour',
            "Votre solde est maintenant {$profile->xp_points} XP ({$profile->rank_level}).",
            route('attendance.leaderboard'),
            'xp'
        ));

        if ($oldRiskLevel && $oldRiskLevel !== $risk->level) {
            $student->notify(new SmartCampusNotification(
                'Niveau de risque mis a jour',
                "Votre indicateur est maintenant {$risk->level}.",
                route('stagiaire.dashboard'),
                'risk'
            ));
        }
    }

    private function auditAttendance(Attendance $attendance, ?string $oldStatus, string $newStatus, User $actor, string $reason): void
    {
        AttendanceAuditLog::create([
            'attendance_id' => $attendance->id,
            'attendance_session_id' => $attendance->attendance_session_id,
            'stagiaire_id' => $attendance->stagiaire_id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $actor->id,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }

    private function assertAttendanceBelongsToSession(Attendance $attendance, TimetableSession $session): void
    {
        abort_unless($attendance->timetable_session_id === $session->id, 404);
    }

    private function attendancePayload(AttendanceSession $attendanceSession): Collection
    {
        return $attendanceSession->attendances()->get(['stagiaire_id', 'status']);
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
            ? "{$user->name} a tente de marquer sa presence avec un appareil non autorise pour {$session->module->name}."
            : "{$user->name} a tente de marquer sa presence en dehors du reseau autorise pour {$session->module->name}.";

        User::whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])->get()
            ->each(fn (User $admin) => $admin->notify(new SmartCampusNotification(
                'Tentative de presence suspecte',
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

    private function attendanceSummary(TimetableSession $session, ?Collection $records = null): array
    {
        $records ??= $session->attendances;
        $counts = $records->countBy('status');
        $totalStudents = max(1, $session->group->stagiaires()->approved()->count());
        $presentLike = collect(Attendance::acceptedStatuses())->sum(fn (string $status) => (int) $counts->get($status, 0));
        $late = collect(Attendance::lateStatuses())->sum(fn (string $status) => (int) $counts->get($status, 0));

        return [
            'present' => (int) $counts->get(Attendance::STATUS_PRESENT, 0),
            'absent' => (int) $counts->get(Attendance::STATUS_ABSENT, 0),
            'late' => $late,
            'justified' => (int) $counts->get(Attendance::STATUS_JUSTIFIED, 0),
            'pending' => (int) $counts->get(Attendance::STATUS_LATE_PENDING, 0) + (int) $counts->get(Attendance::STATUS_SEVERE_LATE_PENDING, 0),
            'marked' => $records->count(),
            'missing' => max(0, $totalStudents - $records->count()),
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
        $presentLike = collect(Attendance::acceptedStatuses())->sum(fn (string $status) => (int) $counts->get($status, 0));
        $late = collect(Attendance::lateStatuses())->sum(fn (string $status) => (int) $counts->get($status, 0));

        return [
            'present' => (int) $counts->get(Attendance::STATUS_PRESENT, 0),
            'absent' => (int) $counts->get(Attendance::STATUS_ABSENT, 0),
            'late' => $late,
            'justified' => (int) $counts->get(Attendance::STATUS_JUSTIFIED, 0),
            'attendanceRate' => round(($presentLike / $total) * 100, 1),
        ];
    }

    private function mostAbsentStudents(int $limit = 8)
    {
        return User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->with(['group', 'riskScore', 'presenceProfile'])
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query->where('status', Attendance::STATUS_ABSENT),
                'attendances as late_count' => fn ($query) => $query->whereIn('status', Attendance::lateStatuses()),
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->take($limit)
            ->get();
    }

    private function bestPresenceGroups(): Collection
    {
        return StudentPresenceProfile::query()
            ->join('users', 'student_presence_profiles.stagiaire_id', '=', 'users.id')
            ->join('groups', 'users.group_id', '=', 'groups.id')
            ->selectRaw('groups.code as group_code, round(avg(student_presence_profiles.xp_points), 1) as average_xp, round(avg(student_presence_profiles.attendance_streak), 1) as average_streak')
            ->groupBy('groups.code')
            ->orderByDesc('average_xp')
            ->take(8)
            ->get();
    }
}
