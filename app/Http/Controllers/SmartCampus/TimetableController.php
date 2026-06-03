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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    public function index(Request $request): View
    {
        $formData = $this->formData();
        $selectedGroupId = $request->integer('group_id') ?: $formData['groups']->first()?->id;
        $selectedGroup = $formData['groups']->firstWhere('id', $selectedGroupId);

        return view('timetable.index', $formData + [
            'gridSessions' => TimetableSession::with(['group.filiere', 'module', 'room', 'formateur'])
                ->when($selectedGroupId, fn ($query) => $query->where('group_id', $selectedGroupId))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->get(),
            'scheduleLabel' => $selectedGroup?->name ?? 'Tous les groupes',
            'selectedGroupId' => $selectedGroupId,
            'sessions' => TimetableSession::with(['group.filiere', 'module', 'room', 'formateur'])
                ->when($selectedGroupId, fn ($query) => $query->where('group_id', $selectedGroupId))
                ->orderBy('day_of_week')
                ->orderBy('starts_at')
                ->paginate(20),
        ]);
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

        return redirect()->route('timetable.index')->with('status', 'Session created.');
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

        return redirect()->route('timetable.index')->with('status', 'Session updated.');
    }

    public function destroy(TimetableSession $session): RedirectResponse
    {
        $this->notifyScheduleChange($session, 'Timetable session removed', 'A session was removed from your timetable.');
        $session->delete();

        return back()->with('status', 'Session deleted.');
    }

    public function mySchedule(): View
    {
        $user = auth()->user();

        $sessions = TimetableSession::with(['group', 'module', 'room', 'formateur'])
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
                1 => 'Monday',
                2 => 'Tuesday',
                3 => 'Wednesday',
                4 => 'Thursday',
                5 => 'Friday',
                6 => 'Saturday',
                7 => 'Sunday',
            ],
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
