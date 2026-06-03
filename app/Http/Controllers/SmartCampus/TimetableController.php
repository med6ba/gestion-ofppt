<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimetableSessionRequest;
use App\Models\Group;
use App\Models\Room;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\TimetableConflictService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $formData = $this->formData();
        $latestSession = TimetableSession::whereBetween('day_of_week', [1, 6])
            ->orderByDesc('starts_on')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();
        $selectedGroupId = $request->integer('group_id') ?: ($latestSession?->group_id ?? $formData['groups']->first()?->id);
        $selectedGroup = $formData['groups']->firstWhere('id', $selectedGroupId);
        $latestWeekStart = $this->latestWeekStart(groupId: $selectedGroupId) ?? now()->startOfWeek();
        $selectedWeekStart = $this->selectedWeekStart($request, $latestWeekStart);
        $selectedWeekEnd = $this->workWeekEnd($selectedWeekStart);
        $weekHistory = $this->weekHistory(groupId: $selectedGroupId);

        return view('timetable.index', $formData + [
            'gridSessions' => TimetableSession::with(['group.filiere', 'module', 'room', 'formateur'])
                ->forWeek($selectedWeekStart)
                ->when($selectedGroupId, fn ($query) => $query->where('group_id', $selectedGroupId))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
            'scheduleLabel' => $selectedGroup?->name ?? 'Tous les groupes',
            'selectedGroupId' => $selectedGroupId,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'latestWeekStart' => $latestWeekStart,
            'weekHistory' => $weekHistory,
            'isSelectedWeekLatest' => $selectedWeekStart->isSameDay($latestWeekStart),
            'isSelectedWeekActive' => $selectedWeekStart->isSameDay($latestWeekStart),
            'sessions' => TimetableSession::with(['group.filiere', 'module', 'room', 'formateur'])
                ->forWeek($selectedWeekStart)
                ->when($selectedGroupId, fn ($query) => $query->where('group_id', $selectedGroupId))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->paginate(20)
                ->appends($request->only(['group_id', 'week_start'])),
        ]);
    }

    public function activateWeek(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'group_id' => ['nullable', 'integer'],
        ]);

        $weekStart = Carbon::parse($data['week_start'])->startOfWeek();

        return redirect()->route('timetable.index', array_filter([
            'group_id' => $data['group_id'] ?? null,
            'week_start' => $weekStart->toDateString(),
        ]))->with('status', 'Semaine affichee. Les utilisateurs voient automatiquement la derniere semaine disponible.');
    }

    public function store(StoreTimetableSessionRequest $request, TimetableConflictService $conflicts): RedirectResponse
    {
        $data = $this->normalizedWeekData($request->validated());
        $this->assertFormateur($data['formateur_id']);

        if ($message = $conflicts->firstConflict($data)) {
            return back()->withErrors(['starts_at' => $message])->withInput();
        }

        $session = TimetableSession::create($data + [
            'created_by' => auth()->id(),
            'status' => 'scheduled',
        ]);

        $this->syncTeachingAssignments($session);
        $this->announceNewTimetable($session);

        return redirect()->route('timetable.index', $this->sessionIndexParams($session))->with('status', 'Seance ajoutee et annonce envoyee par email.');
    }

    public function edit(TimetableSession $session): View
    {
        return view('timetable.edit', $this->formData() + [
            'session' => $session->load(['group', 'module', 'room', 'formateur']),
            'selectedGroupId' => $session->group_id,
            'selectedWeekStart' => $session->starts_on->copy()->startOfWeek(),
            'selectedWeekEnd' => $this->workWeekEnd($session->starts_on),
        ]);
    }

    public function update(StoreTimetableSessionRequest $request, TimetableSession $session, TimetableConflictService $conflicts): RedirectResponse
    {
        $data = $this->normalizedWeekData($request->validated());
        $this->assertFormateur($data['formateur_id']);

        if ($message = $conflicts->firstConflict($data, $session->id)) {
            return back()->withErrors(['starts_at' => $message])->withInput();
        }

        $session->update($data + ['status' => 'changed']);
        $this->syncTeachingAssignments($session);
        $this->notifyScheduleChange($session, 'Timetable updated', ($data['change_note'] ?? null) ?: 'A session in your timetable has changed.');

        return redirect()->route('timetable.index', $this->sessionIndexParams($session))->with('status', 'Seance mise a jour.');
    }

    public function destroy(TimetableSession $session): RedirectResponse
    {
        $redirectParams = $this->sessionIndexParams($session);

        $this->notifyScheduleChange($session, 'Timetable session removed', 'A session was removed from your timetable.');
        $session->delete();

        return redirect()->route('timetable.index', $redirectParams)->with('status', 'Seance supprimee.');
    }

    public function mySchedule(Request $request): View
    {
        $user = auth()->user();
        $latestWeekStart = $this->latestWeekStart(user: $user) ?? now()->startOfWeek();
        $selectedWeekStart = $this->selectedWeekStart($request, $latestWeekStart);
        $selectedWeekEnd = $this->workWeekEnd($selectedWeekStart);

        $sessions = TimetableSession::with(['group', 'module', 'room', 'formateur'])
            ->forWeek($selectedWeekStart)
            ->when($user->isFormateur(), fn ($query) => $query->where('formateur_id', $user->id))
            ->when($user->isStagiaire(), fn ($query) => $query->where('group_id', $user->group_id))
            ->orderBy('day_of_week')
            ->orderBy('starts_at')
            ->get();

        $scheduleLabel = match (true) {
            $user->isStagiaire() => $user->group?->name ?? 'Mon groupe',
            $user->isFormateur() => $sessions->pluck('group.code')->unique()->filter()->join(' / ') ?: 'Mes groupes',
            default => 'Emploi du temps',
        };

        return view('timetable.mine', [
            'gridSessions' => $sessions,
            'scheduleLabel' => $scheduleLabel,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'latestWeekStart' => $latestWeekStart,
            'weekHistory' => $this->weekHistory(user: $user),
            'isSelectedWeekLatest' => $selectedWeekStart->isSameDay($latestWeekStart),
            'isSelectedWeekActive' => $selectedWeekStart->isSameDay($latestWeekStart),
            'weekDays' => $this->weekDays(),
        ]);
    }

    private function formData(): array
    {
        return [
            'groups' => Group::with('filiere')->orderBy('code')->get(),
            'modules' => TrainingModule::orderBy('code')->get(),
            'formateurs' => User::role(User::ROLE_FORMATEUR)->approved()->orderBy('name')->get(),
            'rooms' => Room::orderBy('code')->get(),
            'weekDays' => $this->weekDays(),
        ];
    }

    private function weekDays(): array
    {
        return [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
        ];
    }

    private function selectedWeekStart(Request $request, Carbon $fallback): Carbon
    {
        if (!$request->filled('week_start')) {
            return $fallback->copy();
        }

        try {
            return Carbon::parse($request->input('week_start'))->startOfWeek();
        } catch (\Throwable) {
            return $fallback->copy();
        }
    }

    private function latestWeekStart(?User $user = null, ?int $groupId = null): ?Carbon
    {
        $startsOn = $this->visibleTimetableQuery($user, $groupId)
            ->orderByDesc('starts_on')
            ->value('starts_on');

        return $startsOn ? Carbon::parse($startsOn)->startOfWeek() : null;
    }

    private function weekHistory(?User $user = null, ?int $groupId = null): Collection
    {
        return $this->visibleTimetableQuery($user, $groupId)
            ->orderByDesc('starts_on')
            ->pluck('starts_on')
            ->map(fn ($startsOn) => Carbon::parse($startsOn)->startOfWeek())
            ->unique(fn (Carbon $weekStart) => $weekStart->toDateString())
            ->values();
    }

    private function visibleTimetableQuery(?User $user = null, ?int $groupId = null)
    {
        return TimetableSession::query()
            ->whereBetween('day_of_week', [1, 6])
            ->when($groupId, fn ($query) => $query->where('group_id', $groupId))
            ->when($user?->isFormateur(), fn ($query) => $query->where('formateur_id', $user->id))
            ->when($user?->isStagiaire(), fn ($query) => $query->where('group_id', $user->group_id));
    }

    private function normalizedWeekData(array $data): array
    {
        $weekStart = Carbon::parse($data['starts_on'])->startOfWeek();

        $data['starts_on'] = $weekStart->toDateString();
        $data['ends_on'] = $this->workWeekEnd($weekStart)->toDateString();
        $data['week_number'] = $weekStart->weekOfYear;

        return $data;
    }

    private function workWeekEnd(Carbon $weekStart): Carbon
    {
        return $weekStart->copy()->startOfWeek()->addDays(5);
    }

    private function sessionIndexParams(TimetableSession $session): array
    {
        return [
            'group_id' => $session->group_id,
            'week_start' => $session->starts_on->copy()->startOfWeek()->toDateString(),
        ];
    }

    private function assertFormateur(int $formateurId): void
    {
        abort_unless(User::whereKey($formateurId)->where('role', User::ROLE_FORMATEUR)->exists(), 422);
    }

    private function syncTeachingAssignments(TimetableSession $session): void
    {
        $session->formateur->teachingGroups()->syncWithoutDetaching([
            $session->group_id => ['module_id' => $session->module_id],
        ]);

        $session->formateur->teachingModules()->syncWithoutDetaching([$session->module_id]);
    }

    private function notifyScheduleChange(TimetableSession $session, string $title, string $body): void
    {
        $url = route('timetable.mine');

        $session->formateur->notify(new SmartCampusNotification($title, $body, $url, 'schedule'));
        $session->group->stagiaires()->approved()->get()
            ->each(fn (User $stagiaire) => $stagiaire->notify(new SmartCampusNotification($title, $body, $url, 'schedule')));
    }

    private function announceNewTimetable(TimetableSession $session): void
    {
        $session->loadMissing(['group', 'module', 'room', 'formateur']);
        $weekStart = $session->starts_on->copy()->startOfWeek();
        $weekEnd = $this->workWeekEnd($weekStart);
        $adminUrl = route('timetable.index', $this->sessionIndexParams($session));
        $title = 'Nouvel emploi du temps publie';
        $body = sprintf(
            'Un nouvel emploi du temps a ete ajoute pour le groupe %s, semaine %d (%s - %s).',
            $session->group->code,
            $weekStart->weekOfYear,
            $weekStart->format('d/m/Y'),
            $weekEnd->format('d/m/Y'),
        );

        User::approved()
            ->where('enabled', true)
            ->orderBy('id')
            ->cursor()
            ->each(function (User $user) use ($title, $body, $adminUrl) {
                $url = $user->hasRole([User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])
                    ? $adminUrl
                    : route('timetable.mine');

                $user->notify(new SmartCampusNotification($title, $body, $url, 'schedule', sendMail: true));
            });
    }
}
