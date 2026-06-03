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
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class TimetableController extends Controller
{
    private const ACTIVE_WEEK_CACHE_KEY = 'smartcampus.timetable.active_week_start';

    public function index(Request $request): View
    {
        $formData = $this->formData();
        $selectedGroupId = $request->integer('group_id') ?: $formData['groups']->first()?->id;
        $selectedGroup = $formData['groups']->firstWhere('id', $selectedGroupId);
        $activeWeekStart = $this->activeWeekStart();
        $selectedWeekStart = $this->selectedWeekStart($request, $activeWeekStart);
        $selectedWeekEnd = $selectedWeekStart->copy()->endOfWeek();

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
            'activeWeekStart' => $activeWeekStart,
            'isSelectedWeekActive' => $selectedWeekStart->isSameDay($activeWeekStart),
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

        Cache::forever(self::ACTIVE_WEEK_CACHE_KEY, $weekStart->toDateString());

        return redirect()->route('timetable.index', array_filter([
            'group_id' => $data['group_id'] ?? null,
            'week_start' => $weekStart->toDateString(),
        ]))->with('status', 'Semaine active mise a jour pour tous les utilisateurs.');
    }

    public function store(StoreTimetableSessionRequest $request, TimetableConflictService $conflicts): RedirectResponse
    {
        $data = $request->validated();
        $this->assertFormateur($data['formateur_id']);

        if ($message = $conflicts->firstConflict($data)) {
            return back()->withErrors(['starts_at' => $message])->withInput();
        }

        $session = TimetableSession::create($data + [
            'created_by' => auth()->id(),
            'status' => 'scheduled',
        ]);

        $this->syncTeachingAssignments($session);
        $this->notifyScheduleChange($session, 'New timetable session', 'A new session has been added to your schedule.');

        return redirect()->route('timetable.index', $this->sessionIndexParams($session))->with('status', 'Session created.');
    }

    public function edit(TimetableSession $session): View
    {
        return view('timetable.edit', $this->formData() + [
            'session' => $session->load(['group', 'module', 'room', 'formateur']),
        ]);
    }

    public function update(StoreTimetableSessionRequest $request, TimetableSession $session, TimetableConflictService $conflicts): RedirectResponse
    {
        $data = $request->validated();
        $this->assertFormateur($data['formateur_id']);

        if ($message = $conflicts->firstConflict($data, $session->id)) {
            return back()->withErrors(['starts_at' => $message])->withInput();
        }

        $session->update($data + ['status' => 'changed']);
        $this->syncTeachingAssignments($session);
        $this->notifyScheduleChange($session, 'Timetable updated', $data['change_note'] ?: 'A session in your timetable has changed.');

        return redirect()->route('timetable.index', $this->sessionIndexParams($session))->with('status', 'Session updated.');
    }

    public function destroy(TimetableSession $session): RedirectResponse
    {
        $redirectParams = $this->sessionIndexParams($session);

        $this->notifyScheduleChange($session, 'Timetable session removed', 'A session was removed from your timetable.');
        $session->delete();

        return redirect()->route('timetable.index', $redirectParams)->with('status', 'Session deleted.');
    }

    public function mySchedule(): View
    {
        $user = auth()->user();
        $activeWeekStart = $this->activeWeekStart();
        $activeWeekEnd = $activeWeekStart->copy()->endOfWeek();

        $sessions = TimetableSession::with(['group', 'module', 'room', 'formateur'])
            ->forWeek($activeWeekStart)
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
            'selectedWeekStart' => $activeWeekStart,
            'selectedWeekEnd' => $activeWeekEnd,
            'activeWeekStart' => $activeWeekStart,
            'isSelectedWeekActive' => true,
        ]);
    }

    private function formData(): array
    {
        return [
            'groups' => Group::with('filiere')->orderBy('code')->get(),
            'modules' => TrainingModule::orderBy('code')->get(),
            'formateurs' => User::role(User::ROLE_FORMATEUR)->approved()->orderBy('name')->get(),
            'rooms' => Room::orderBy('code')->get(),
            'weekDays' => [
                1 => 'Lundi',
                2 => 'Mardi',
                3 => 'Mercredi',
                4 => 'Jeudi',
                5 => 'Vendredi',
                6 => 'Samedi',
                7 => 'Dimanche',
            ],
        ];
    }

    private function activeWeekStart(): Carbon
    {
        $cached = Cache::get(self::ACTIVE_WEEK_CACHE_KEY);

        if ($cached) {
            try {
                return Carbon::parse($cached)->startOfWeek();
            } catch (\Throwable) {
                Cache::forget(self::ACTIVE_WEEK_CACHE_KEY);
            }
        }

        return now()->startOfWeek();
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
}
