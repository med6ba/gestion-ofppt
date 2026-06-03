<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAuditLog;
use App\Models\AttendanceAttempt;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\RiskScore;
use App\Models\Room;
use App\Models\StudentPresenceProfile;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Services\PresenceXpService;
use App\Services\RiskScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return redirect()->route(auth()->user()->dashboardRoute());
    }

    public function directeur(RiskScoreService $riskScoreService, PresenceXpService $presenceXpService): View
    {
        $riskScoreService->refreshAll();
        $presenceXpService->refreshAll();

        $attendanceStats = $this->attendanceStats();

        return view('dashboards.directeur', [
            'stats' => [
                'stagiaires' => User::role(User::ROLE_STAGIAIRE)->count(),
                'formateurs' => User::role(User::ROLE_FORMATEUR)->count(),
                'groups' => Group::count(),
                'todaySessions' => TimetableSession::forDate(now())->count(),
                'absenceRate' => $attendanceStats['absenceRate'],
                'riskStudents' => RiskScore::where('level', 'High')->count(),
                'suspiciousAttempts' => AttendanceAttempt::count(),
            ],
            'riskScores' => RiskScore::with('stagiaire.group')->orderByDesc('score')->take(6)->get(),
            'topProfiles' => StudentPresenceProfile::with('stagiaire.group')->orderByDesc('xp_points')->take(6)->get(),
            'auditLogs' => AttendanceAuditLog::with(['stagiaire.group', 'changedBy'])->latest('created_at')->take(6)->get(),
            'suspiciousAttempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(6)->get(),
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'formateur', 'activeAttendanceSession'])->forDate(now())->orderBy('starts_at')->take(8)->get(),
            'roomOccupancy' => $this->roomOccupancy(),
            'attendanceChart' => $attendanceStats['chart'],
            'mostAbsentStudents' => $this->mostAbsentStudents(6),
        ]);
    }

    public function surveillant(RiskScoreService $riskScoreService, PresenceXpService $presenceXpService): View
    {
        $riskScoreService->refreshAll();
        $presenceXpService->refreshAll();

        return view('dashboards.surveillant', [
            'pendingStagiaires' => User::role(User::ROLE_STAGIAIRE)->where('approval_status', 'pending')->count(),
            'riskScores' => RiskScore::with('stagiaire.group')->orderByDesc('score')->take(8)->get(),
            'topProfiles' => StudentPresenceProfile::with('stagiaire.group')->orderByDesc('xp_points')->take(6)->get(),
            'severeLateQueue' => Attendance::with(['stagiaire.group', 'session.module', 'session.formateur'])
                ->where('status', Attendance::STATUS_SEVERE_LATE_PENDING)
                ->latest('check_in_at')
                ->take(8)
                ->get(),
            'suspiciousAttempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(6)->get(),
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'formateur', 'activeAttendanceSession'])->forDate(now())->orderBy('starts_at')->get(),
            'roomOccupancy' => $this->roomOccupancy(),
            'attendanceChart' => $this->attendanceStats()['chart'],
            'recentConversations' => Conversation::with('participants')->latest('last_message_at')->take(5)->get(),
            'mostAbsentStudents' => $this->mostAbsentStudents(6),
        ]);
    }

    public function formateur(): View
    {
        $user = auth()->user();
        $todaySessions = TimetableSession::with(['group', 'module', 'room', 'activeQrSession', 'activeAttendanceSession'])
            ->where('formateur_id', $user->id)
            ->forDate(now())
            ->orderBy('starts_at')
            ->get();

        $groupAbsenceRates = $user->teachingGroups()
            ->withCount('stagiaires')
            ->get()
            ->map(function (Group $group) use ($user) {
                $total = Attendance::whereHas('session', fn ($query) => $query->where('formateur_id', $user->id)->where('group_id', $group->id))->count();
                $absent = Attendance::whereHas('session', fn ($query) => $query->where('formateur_id', $user->id)->where('group_id', $group->id))->where('status', Attendance::STATUS_ABSENT)->count();

                return [
                    'label' => $group->code,
                    'rate' => $total ? round(($absent / $total) * 100, 1) : 0,
                ];
            });

        return view('dashboards.formateur', [
            'todaySessions' => $todaySessions,
            'nextSession' => $todaySessions->firstWhere('starts_at', '>=', now()->format('H:i:s')) ?? $todaySessions->first(),
            'groups' => $user->teachingGroups()->with('filiere')->get(),
            'groupAbsenceRates' => $groupAbsenceRates,
            'pendingLateCount' => Attendance::whereHas('session', fn ($query) => $query->where('formateur_id', $user->id))
                ->where('status', Attendance::STATUS_LATE_PENDING)
                ->count(),
            'leaderboard' => StudentPresenceProfile::with('stagiaire.group')
                ->whereHas('stagiaire', fn ($query) => $query->whereIn('group_id', $user->teachingGroups()->pluck('groups.id')))
                ->orderByDesc('xp_points')
                ->take(5)
                ->get(),
            'unreadMessages' => $this->unreadConversationCount($user),
        ]);
    }

    public function formateurTeaching(): View
    {
        $user = auth()->user();
        abort_unless($user->isFormateur(), 403);

        $groups = $user->teachingGroups()
            ->with([
                'filiere',
                'stagiaires' => fn ($query) => $query->approved()->with(['riskScore', 'presenceProfile'])->orderBy('name'),
            ])
            ->orderBy('code')
            ->get();

        $moduleIds = $groups->pluck('pivot.module_id')
            ->merge($user->timetableSessions()->pluck('module_id'))
            ->filter()
            ->unique()
            ->values();

        $students = $groups
            ->flatMap(fn (Group $group) => $group->stagiaires)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('formateur.teaching', [
            'activeTab' => in_array(request('tab'), ['groups', 'modules', 'students'], true) ? request('tab') : 'groups',
            'groups' => $groups,
            'modules' => TrainingModule::query()->whereKey($moduleIds)->orderBy('code')->get(),
            'students' => $students,
            'upcomingSessions' => TimetableSession::with(['group', 'module', 'room'])
                ->where('formateur_id', $user->id)
                ->whereDate('ends_on', '>=', now()->startOfWeek()->toDateString())
                ->orderBy('starts_on')
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->take(10)
                ->get(),
        ]);
    }

    public function stagiaire(RiskScoreService $riskScoreService, PresenceXpService $presenceXpService): View
    {
        $user = auth()->user();
        $riskScore = $riskScoreService->updateFor($user);
        $presenceProfile = $presenceXpService->refreshFor($user);

        $todaySessions = TimetableSession::with(['module', 'room', 'formateur', 'activeAttendanceSession'])
            ->where('group_id', $user->group_id)
            ->forDate(now())
            ->orderBy('starts_at')
            ->get();

        $activeLateWindows = $todaySessions
            ->pluck('activeAttendanceSession')
            ->filter()
            ->filter(fn ($attendanceSession) => $attendanceSession->isLateDeclarationOpen())
            ->reject(fn ($attendanceSession) => Attendance::where('attendance_session_id', $attendanceSession->id)->where('stagiaire_id', $user->id)->exists())
            ->values();
        $activeLateWindows->each->load(['timetableSession.module', 'timetableSession.room', 'formateur']);

        $tomorrow = now()->copy()->addDay();

        return view('dashboards.stagiaire', [
            'todaySessions' => $todaySessions,
            'tomorrowSessions' => TimetableSession::with(['module', 'room', 'formateur'])
                ->where('group_id', $user->group_id)
                ->forDate($tomorrow)
                ->orderBy('starts_at')
                ->get(),
            'nextSession' => $todaySessions->firstWhere('starts_at', '>=', now()->format('H:i:s')) ?? $todaySessions->first(),
            'attendanceBySession' => Attendance::where('stagiaire_id', $user->id)
                ->whereIn('timetable_session_id', $todaySessions->pluck('id'))
                ->get()
                ->keyBy('timetable_session_id'),
            'attendanceCounts' => Attendance::where('stagiaire_id', $user->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'riskScore' => $riskScore,
            'presenceProfile' => $presenceProfile,
            'activeLateWindows' => $activeLateWindows,
            'unreadMessages' => $this->unreadConversationCount($user),
        ]);
    }

    public function stagiaireModules(): View
    {
        $user = auth()->user();
        abort_unless($user->isStagiaire(), 403);

        $sessions = TimetableSession::with(['module', 'formateur', 'room'])
            ->where('group_id', $user->group_id)
            ->whereDate('ends_on', '>=', now()->startOfWeek()->toDateString())
            ->orderBy('starts_on')
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        $modules = $sessions
            ->groupBy('module_id')
            ->map(function (Collection $moduleSessions) {
                $firstSession = $moduleSessions->first();

                return [
                    'module' => $firstSession->module,
                    'sessions' => $moduleSessions,
                    'formateurs' => $moduleSessions->pluck('formateur')->filter()->unique('id')->values(),
                    'rooms' => $moduleSessions->pluck('room.code')->filter()->unique()->values(),
                ];
            })
            ->values();

        return view('stagiaire.modules', [
            'group' => $user->group,
            'modules' => $modules,
        ]);
    }

    private function attendanceStats(): array
    {
        $counts = Attendance::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = max(1, $counts->sum());
        $absenceRate = round(((int) $counts->get('absent', 0) / $total) * 100, 1);

        return [
            'absenceRate' => $absenceRate,
            'chart' => [
                'labels' => ['Present', 'Absent', 'Late', 'Justified'],
                'data' => [
                    (int) $counts->get('present', 0),
                    (int) $counts->get('absent', 0),
                    collect(Attendance::lateStatuses())->sum(fn (string $status) => (int) $counts->get($status, 0)),
                    (int) $counts->get('justified', 0),
                ],
            ],
        ];
    }

    private function roomOccupancy(): Collection
    {
        $totalSessions = max(1, TimetableSession::count());

        return Room::query()
            ->withCount('sessions')
            ->orderByDesc('sessions_count')
            ->take(6)
            ->get()
            ->map(fn (Room $room) => [
                'room' => $room->code,
                'rate' => round(($room->sessions_count / $totalSessions) * 100, 1),
            ]);
    }

    private function unreadConversationCount(User $user): int
    {
        return $user->conversations()
            ->whereHas('messages', function ($query) use ($user) {
                $query->where('sender_id', '!=', $user->id)
                    ->where(function ($query) {
                        $query->whereColumn('messages.created_at', '>', 'conversation_participants.last_read_at')
                            ->orWhereNull('conversation_participants.last_read_at');
                    });
            })
            ->count();
    }

    private function mostAbsentStudents(int $limit = 6): Collection
    {
        return User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->with(['group', 'riskScore'])
            ->withCount([
                'attendances as absences_count' => fn ($query) => $query->where('status', Attendance::STATUS_ABSENT),
                'attendances as late_count' => fn ($query) => $query->whereIn('status', Attendance::lateStatuses()),
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->take($limit)
            ->get();
    }
}
