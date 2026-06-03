<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\Conversation;
use App\Models\Group;
use App\Models\RiskScore;
use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\User;
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

    public function directeur(RiskScoreService $riskScoreService): View
    {
        $riskScoreService->refreshAll();

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
            'suspiciousAttempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(6)->get(),
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'formateur'])->forDate(now())->orderBy('starts_at')->take(8)->get(),
            'roomOccupancy' => $this->roomOccupancy(),
            'attendanceChart' => $attendanceStats['chart'],
            'mostAbsentStudents' => $this->mostAbsentStudents(6),
        ]);
    }

    public function surveillant(RiskScoreService $riskScoreService): View
    {
        $riskScoreService->refreshAll();

        return view('dashboards.surveillant', [
            'pendingStagiaires' => User::role(User::ROLE_STAGIAIRE)->where('approval_status', 'pending')->count(),
            'riskScores' => RiskScore::with('stagiaire.group')->orderByDesc('score')->take(8)->get(),
            'suspiciousAttempts' => AttendanceAttempt::with(['stagiaire', 'session.group'])->latest('created_at')->take(6)->get(),
            'todaySessions' => TimetableSession::with(['group', 'module', 'room', 'formateur'])->forDate(now())->orderBy('starts_at')->get(),
            'roomOccupancy' => $this->roomOccupancy(),
            'attendanceChart' => $this->attendanceStats()['chart'],
            'recentConversations' => Conversation::with('participants')->latest('last_message_at')->take(5)->get(),
            'mostAbsentStudents' => $this->mostAbsentStudents(6),
        ]);
    }

    public function formateur(): View
    {
        $user = auth()->user();
        $todaySessions = TimetableSession::with(['group', 'module', 'room', 'activeQrSession'])
            ->where('formateur_id', $user->id)
            ->forDate(now())
            ->orderBy('starts_at')
            ->get();

        $groupAbsenceRates = $user->teachingGroups()
            ->withCount('stagiaires')
            ->get()
            ->map(function (Group $group) use ($user) {
                $total = Attendance::whereHas('session', fn ($query) => $query->where('formateur_id', $user->id)->where('group_id', $group->id))->count();
                $absent = Attendance::whereHas('session', fn ($query) => $query->where('formateur_id', $user->id)->where('group_id', $group->id))->where('status', 'absent')->count();

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
            'unreadMessages' => $this->unreadConversationCount($user),
        ]);
    }

    public function stagiaire(RiskScoreService $riskScoreService): View
    {
        $user = auth()->user();
        $riskScore = $riskScoreService->updateFor($user);

        $todaySessions = TimetableSession::with(['module', 'room', 'formateur'])
            ->where('group_id', $user->group_id)
            ->forDate(now())
            ->orderBy('starts_at')
            ->get();

        $tomorrow = now()->copy()->addDay();

        return view('dashboards.stagiaire', [
            'todaySessions' => $todaySessions,
            'tomorrowSessions' => TimetableSession::with(['module', 'room', 'formateur'])
                ->where('group_id', $user->group_id)
                ->forDate($tomorrow)
                ->orderBy('starts_at')
                ->get(),
            'nextSession' => $todaySessions->firstWhere('starts_at', '>=', now()->format('H:i:s')) ?? $todaySessions->first(),
            'attendanceCounts' => Attendance::where('stagiaire_id', $user->id)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'riskScore' => $riskScore,
            'unreadMessages' => $this->unreadConversationCount($user),
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
                    (int) $counts->get('late', 0),
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
                'attendances as absences_count' => fn ($query) => $query->where('status', 'absent'),
                'attendances as late_count' => fn ($query) => $query->where('status', 'late'),
            ])
            ->orderByDesc('absences_count')
            ->orderByDesc('late_count')
            ->take($limit)
            ->get();
    }
}
