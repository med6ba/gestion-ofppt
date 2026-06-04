<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeeklyTimetableRequest;
use App\Http\Requests\StoreTimetableSessionRequest;
use App\Models\Group;
use App\Models\Room;
use App\Models\SessionCancellationRequest;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Models\WeeklyTimetable;
use App\Notifications\SmartCampusNotification;
use App\Services\TimetableConflictService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableController extends Controller
{
    // ──────────────────────────────────────────────
    // Surveillant/Directeur: Management page
    // ──────────────────────────────────────────────
    public function index(Request $request): View
    {
        $formData = $this->formData();
        $selectedGroupId = $request->integer('group_id') ?: $formData['groups']->first()?->id;
        $selectedGroup = $formData['groups']->firstWhere('id', $selectedGroupId);

        // Find latest published WeeklyTimetable for this group
        $latestTimetable = WeeklyTimetable::forGroup($selectedGroupId)
            ->where('status', 'published')
            ->orderByDesc('week_start_date')
            ->first();

        // Selected week from query or latest
        $selectedWeekStart = $request->filled('week_start')
            ? Carbon::parse($request->input('week_start'))->startOfWeek()
            : ($latestTimetable?->week_start_date?->startOfWeek() ?? now()->startOfWeek());

        $selectedWeekEnd = $selectedWeekStart->copy()->addDays(5);

        // Find the WeeklyTimetable for the selected week and group
        $weeklyTimetable = WeeklyTimetable::forGroup($selectedGroupId)
            ->forWeek($selectedWeekStart)
            ->first();

        // Sessions for the grid
        $gridSessions = $weeklyTimetable
            ? $weeklyTimetable->sessions()
                ->with(['group.filiere', 'module', 'room', 'formateur'])
                ->orderBy('day_of_week')->orderBy('starts_at')->get()
            : collect();

        // Week history
        $weekHistory = WeeklyTimetable::forGroup($selectedGroupId)
            ->orderByDesc('week_start_date')
            ->get(['id', 'week_start_date', 'week_end_date', 'status']);

        // Pending cancellation requests (for surveillant)
        $cancellationRequests = auth()->user()->isSurveillant()
            ? SessionCancellationRequest::pending()
                ->with(['timetableSession.module', 'timetableSession.group', 'timetableSession.room', 'formateur'])
                ->latest()->get()
            : collect();

        return view('timetable.index', $formData + [
            'gridSessions' => $gridSessions,
            'weeklyTimetable' => $weeklyTimetable,
            'scheduleLabel' => $selectedGroup?->name ?? 'Tous les groupes',
            'selectedGroupId' => $selectedGroupId,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'weekHistory' => $weekHistory,
            'cancellationRequests' => $cancellationRequests,
            'weekDays' => $this->weekDays(),
        ]);
    }

    // ──────────────────────────────────────────────
    // Weekly Timetable CRUD
    // ──────────────────────────────────────────────
    public function storeWeeklyTimetable(StoreWeeklyTimetableRequest $request): JsonResponse
    {
        $weekStart = Carbon::parse($request->validated('week_start_date'))->startOfWeek();

        // Check unique
        if (WeeklyTimetable::where('group_id', $request->validated('group_id'))->whereDate('week_start_date', $weekStart)->exists()) {
            return response()->json(['success' => false, 'errors' => ['Un emploi du temps existe déjà pour ce groupe cette semaine.']], 422);
        }

        $isCurrentOrPast = $weekStart->copy()->startOfDay()->lte(now()->startOfWeek()->startOfDay());
        $status = $isCurrentOrPast ? 'published' : 'draft';

        $wt = WeeklyTimetable::create([
            'group_id' => $request->validated('group_id'),
            'week_start_date' => $weekStart,
            'week_end_date' => $weekStart->copy()->addDays(5),
            'title' => $request->validated('title'),
            'notes' => $request->validated('notes'),
            'status' => $status,
            'published_at' => $isCurrentOrPast ? now() : null,
            'created_by' => auth()->id(),
        ]);

        if ($isCurrentOrPast) {
            $this->announcePublishedTimetable($wt);
        }

        return response()->json([
            'success' => true,
            'message' => 'Emploi du temps créé.',
            'redirect' => route('timetable.index', ['group_id' => $wt->group_id, 'week_start' => $wt->week_start_date->toDateString()]),
        ]);
    }

    public function launchWeeklyTimetable(WeeklyTimetable $weeklyTimetable): RedirectResponse
    {
        $weeklyTimetable->update(['status' => 'published', 'published_at' => now()]);
        $this->announcePublishedTimetable($weeklyTimetable);

        return redirect()->route('timetable.index', [
            'group_id' => $weeklyTimetable->group_id,
            'week_start' => $weeklyTimetable->week_start_date->toDateString(),
        ])->with('status', 'Emploi du temps lancé avec succès. Notifications envoyées.');
    }

    public function duplicateWeeklyTimetable(Request $request, WeeklyTimetable $weeklyTimetable): RedirectResponse
    {
        $request->validate([
            'new_week_start' => ['required', 'date', function ($attr, $value, $fail) {
                if (Carbon::parse($value)->dayOfWeekIso !== 1) {
                    $fail('La date de début doit être un lundi.');
                }
            }],
        ]);

        $newWeekStart = Carbon::parse($request->input('new_week_start'))->startOfWeek();

        if (WeeklyTimetable::where('group_id', $weeklyTimetable->group_id)->whereDate('week_start_date', $newWeekStart)->exists()) {
            return back()->withErrors(['new_week_start' => 'Un emploi du temps existe deja pour ce groupe pendant cette semaine.']);
        }

        $newTimetable = $weeklyTimetable->duplicate($newWeekStart);

        return redirect()->route('timetable.index', [
            'group_id' => $newTimetable->group_id,
            'week_start' => $newTimetable->week_start_date->toDateString(),
        ])->with('status', 'Emploi du temps dupliqué en brouillon.');
    }

    // ──────────────────────────────────────────────
    // Session CRUD (AJAX for modals)
    // ──────────────────────────────────────────────
    public function storeSession(Request $request, TimetableConflictService $conflicts): JsonResponse
    {
        $data = $request->validate([
            'week_start_date' => ['required', 'date'],
            'group_id' => ['required', 'exists:groups,id'],
            'module_id' => ['required', 'exists:modules,id'],
            'formateur_id' => ['required', 'exists:users,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertFormateur($data['formateur_id']);

        $weekStart = Carbon::parse($data['week_start_date'])->startOfWeek();
        $isCurrentOrPast = $weekStart->copy()->startOfDay()->lte(now()->startOfWeek()->startOfDay());

        $wt = WeeklyTimetable::firstOrCreate(
            ['group_id' => $data['group_id'], 'week_start_date' => $weekStart->toDateString()],
            [
                'week_end_date' => $weekStart->copy()->addDays(5)->toDateString(),
                'status' => $isCurrentOrPast ? 'published' : 'draft',
                'published_at' => $isCurrentOrPast ? now() : null,
                'created_by' => auth()->id(),
            ]
        );

        $data['weekly_timetable_id'] = $wt->id;
        $data['starts_on'] = $wt->week_start_date->toDateString();
        $data['ends_on'] = $wt->week_end_date->toDateString();
        $data['week_number'] = $wt->week_start_date->weekOfYear;

        $conflictMessages = $conflicts->allConflicts($data);
        if (!empty($conflictMessages)) {
            return response()->json(['success' => false, 'errors' => $conflictMessages], 422);
        }

        $session = TimetableSession::create($data + [
            'created_by' => auth()->id(),
            'status' => 'scheduled',
        ]);

        $this->syncTeachingAssignments($session);

        if ($wt->isPublished()) {
            $this->notifyScheduleChange($session, 'Nouvelle séance ajoutée', 'Une nouvelle séance a été ajoutée à votre emploi du temps.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Seance ajoutee avec succes.',
            'redirect' => route('timetable.index', ['group_id' => $wt->group_id, 'week_start' => $wt->week_start_date->toDateString()]),
        ]);
    }

    public function updateSession(Request $request, TimetableSession $session, TimetableConflictService $conflicts): JsonResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'exists:modules,id'],
            'formateur_id' => ['required', 'exists:users,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'change_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->assertFormateur($data['formateur_id']);

        $data['group_id'] = $session->group_id;
        $data['starts_on'] = $session->starts_on->toDateString();
        $data['ends_on'] = $session->ends_on->toDateString();

        $conflictMessages = $conflicts->allConflicts($data, $session->id);
        if (!empty($conflictMessages)) {
            return response()->json(['success' => false, 'errors' => $conflictMessages], 422);
        }

        $session->update($data + ['status' => 'changed']);
        $this->syncTeachingAssignments($session);
        $this->notifyScheduleChange($session, 'Emploi du temps modifie', ($data['change_note'] ?? null) ?: 'Une seance de votre emploi du temps a ete modifiee.');

        return response()->json(['success' => true, 'message' => 'Seance mise a jour.']);
    }

    public function destroySession(Request $request, TimetableSession $session): JsonResponse
    {
        $reason = $request->input('reason', '');
        $this->notifyScheduleChange($session, 'Seance supprimee', 'Une seance a ete supprimee de votre emploi du temps.' . ($reason ? ' Raison: ' . $reason : ''));
        $session->delete();

        return response()->json(['success' => true, 'message' => 'Seance supprimee.']);
    }

    public function sessionDetails(TimetableSession $session): JsonResponse
    {
        $session->load(['group.filiere', 'module', 'room', 'formateur', 'weeklyTimetable']);
        return response()->json($session);
    }

    // ──────────────────────────────────────────────
    // Cancellation Workflow
    // ──────────────────────────────────────────────
    public function requestCancellation(Request $request, TimetableSession $session): JsonResponse
    {
        $user = auth()->user();
        abort_unless($session->formateur_id === $user->id, 403);
        abort_if($session->isCancelled(), 422, 'Cette seance est deja annulee.');

        // 2-hour rule: calculate exact session datetime using starts_on + day_of_week offset
        $sessionDate = $session->starts_on->copy()->addDays($session->day_of_week - 1);
        $sessionDateTime = $sessionDate->setTimeFromTimeString($session->starts_at);
        
        if (now()->diffInMinutes($sessionDateTime, false) < 120) {
            return response()->json([
                'success' => false,
                'errors' => ['Vous devez demander l\'annulation au moins 2 heures avant la seance.'],
            ], 422);
        }

        $request->validate(['reason' => ['required', 'string', 'min:10', 'max:1000']]);

        $cancellationRequest = SessionCancellationRequest::create([
            'timetable_session_id' => $session->id,
            'formateur_id' => $user->id,
            'requested_by' => $user->id,
            'reason' => $request->input('reason'),
            'status' => 'pending',
        ]);

        // Notify Surveillant(s)
        $session->loadMissing(['module', 'group', 'room']);
        User::role(User::ROLE_SURVEILLANT)->approved()->get()
            ->each(fn (User $s) => $s->notify(new SmartCampusNotification(
                'Demande d\'annulation de seance',
                sprintf('%s demande l\'annulation de %s (%s, %s). Raison: %s',
                    $user->name, $session->module->name, $session->group->code, $session->timeLabel(), $request->input('reason')),
                route('timetable.index', ['group_id' => $session->group_id]),
                'schedule',
                sendMail: true,
            )));

        return response()->json(['success' => true, 'message' => 'Demande d\'annulation envoyee.']);
    }

    public function cancellationRequests(): View
    {
        $requests = SessionCancellationRequest::pending()
            ->with(['timetableSession.module', 'timetableSession.group', 'timetableSession.room', 'formateur'])
            ->latest()->paginate(20);

        return view('timetable.cancellation-requests', compact('requests'));
    }

    public function approveCancellation(Request $request, SessionCancellationRequest $cancellationRequest): RedirectResponse
    {
        abort_unless($cancellationRequest->isPending(), 422);

        $reviewNote = $request->input('review_note', '');
        $cancellationRequest->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $reviewNote,
        ]);

        $session = $cancellationRequest->timetableSession;
        $session->update([
            'status' => 'cancelled',
            'cancellation_reason' => $cancellationRequest->reason,
            'cancelled_by' => auth()->id(),
            'cancelled_at' => now(),
        ]);

        // Notify formateur
        $session->loadMissing(['module', 'group', 'room', 'formateur']);
        $session->formateur->notify(new SmartCampusNotification(
            'Demande d\'annulation approuvee',
            sprintf('Votre demande d\'annulation pour %s (%s) a ete approuvee.', $session->module->name, $session->group->code),
            route('timetable.mine'),
            'schedule',
            sendMail: true,
        ));

        // Notify group stagiaires
        $session->group->stagiaires()->approved()->get()
            ->each(fn (User $stagiaire) => $stagiaire->notify(new SmartCampusNotification(
                'Seance annulee',
                sprintf('Votre seance de %s avec %s prevue le %s a %s a ete annulee. Raison: %s',
                    $session->module->name, $session->formateur->name,
                    $this->weekDays()[$session->day_of_week] ?? '', substr($session->starts_at, 0, 5),
                    $cancellationRequest->reason),
                route('timetable.mine'),
                'schedule',
                sendMail: true,
            )));

        return redirect()->route('timetable.index', ['group_id' => $session->group_id])
            ->with('status', 'Annulation approuvee. Notifications envoyees.');
    }

    public function rejectCancellation(Request $request, SessionCancellationRequest $cancellationRequest): RedirectResponse
    {
        abort_unless($cancellationRequest->isPending(), 422);

        $cancellationRequest->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_note' => $request->input('review_note', ''),
        ]);

        // Notify formateur
        $session = $cancellationRequest->timetableSession->load('module', 'group');
        $cancellationRequest->formateur->notify(new SmartCampusNotification(
            'Demande d\'annulation refusee',
            sprintf('Votre demande d\'annulation pour %s (%s) a ete refusee.', $session->module->name, $session->group->code),
            route('timetable.mine'),
            'schedule',
            sendMail: true,
        ));

        return redirect()->route('timetable.index', ['group_id' => $session->group_id])
            ->with('status', 'Demande d\'annulation refusee.');
    }

    // ──────────────────────────────────────────────
    // Personal Schedule (all roles)
    // ──────────────────────────────────────────────
    public function mySchedule(Request $request): View
    {
        $user = auth()->user();

        // Get WeeklyTimetables visible to this user
        $timetablesQuery = WeeklyTimetable::published()
            ->when($user->isStagiaire(), fn ($q) => $q->where('group_id', $user->group_id))
            ->when($user->isFormateur(), fn ($q) => $q->whereHas('sessions', fn ($sq) => $sq->where('formateur_id', $user->id)));

        $latestTimetable = (clone $timetablesQuery)->orderByDesc('week_start_date')->first();
        $selectedWeekStart = $request->filled('week_start')
            ? Carbon::parse($request->input('week_start'))->startOfWeek()
            : ($latestTimetable?->week_start_date?->startOfWeek() ?? now()->startOfWeek());
        $selectedWeekEnd = $selectedWeekStart->copy()->addDays(5);

        $sessions = TimetableSession::with(['group', 'module', 'room', 'formateur'])
            ->forWeek($selectedWeekStart)
            ->when($user->isFormateur(), fn ($q) => $q->where('formateur_id', $user->id))
            ->when($user->isStagiaire(), fn ($q) => $q->where('group_id', $user->group_id))
            ->orderBy('day_of_week')->orderBy('starts_at')
            ->get();

        $scheduleLabel = match (true) {
            $user->isStagiaire() => $user->group?->name ?? 'Mon groupe',
            $user->isFormateur() => $sessions->pluck('group.code')->unique()->filter()->join(' / ') ?: 'Mes groupes',
            default => 'Emploi du temps',
        };

        $weekHistory = (clone $timetablesQuery)->orderByDesc('week_start_date')
            ->get(['id', 'week_start_date', 'week_end_date', 'status']);

        return view('timetable.mine', [
            'gridSessions' => $sessions,
            'scheduleLabel' => $scheduleLabel,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'weekHistory' => $weekHistory,
            'weekDays' => $this->weekDays(),
            'isFormateur' => $user->isFormateur(),
        ]);
    }

    public function archive(Request $request): View
    {
        $user = auth()->user();

        $timetables = WeeklyTimetable::archived()
            ->with('group.filiere')
            ->when($user->isStagiaire(), fn ($q) => $q->where('group_id', $user->group_id))
            ->when($user->isFormateur(), fn ($q) => $q->whereHas('sessions', fn ($sq) => $sq->where('formateur_id', $user->id)))
            ->when($request->filled('group_id'), fn ($q) => $q->where('group_id', $request->integer('group_id')))
            ->orderByDesc('week_start_date')
            ->paginate(20);

        $groups = ($user->isDirecteur() || $user->isSurveillant())
            ? Group::with('filiere')->orderBy('code')->get()
            : collect();

        return view('timetable.archive', compact('timetables', 'groups'));
    }

    // ──────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────
    private function formData(): array
    {
        return [
            'groups' => Group::with('filiere')->orderBy('code')->get(),
            'modules' => TrainingModule::orderBy('code')->get(),
            'formateurs' => User::role(User::ROLE_FORMATEUR)->approved()->orderBy('name')->get(),
            'rooms' => Room::orderBy('code')->get(),
            'weekDays' => $this->weekDays(),
            'formateur_modules' => \Illuminate\Support\Facades\DB::table('formateur_module')->get(),
        ];
    }

    private function weekDays(): array
    {
        return [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi'];
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

    private function announcePublishedTimetable(WeeklyTimetable $wt): void
    {
        $wt->loadMissing(['group', 'sessions.formateur']);
        $adminUrl = route('timetable.index', ['group_id' => $wt->group_id, 'week_start' => $wt->week_start_date->toDateString()]);
        $title = 'Nouvel emploi du temps publie';
        $body = sprintf(
            'Un nouvel emploi du temps a ete publie pour le groupe %s, semaine du %s au %s.',
            $wt->group->code ?? $wt->group->name,
            $wt->week_start_date->format('d/m/Y'),
            $wt->week_end_date->format('d/m/Y'),
        );

        // Notify Directeur + Surveillant
        User::whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])->approved()->get()
            ->each(fn (User $u) => $u->notify(new SmartCampusNotification($title, $body, $adminUrl, 'schedule', sendMail: true)));

        // Notify affected formateurs (only those with sessions in this timetable)
        $formateurIds = $wt->sessions->pluck('formateur_id')->unique();
        User::whereIn('id', $formateurIds)->get()
            ->each(fn (User $u) => $u->notify(new SmartCampusNotification($title, $body, route('timetable.mine'), 'schedule', sendMail: true)));

        // Notify group stagiaires
        $wt->group->stagiaires()->approved()->get()
            ->each(fn (User $u) => $u->notify(new SmartCampusNotification($title, $body, route('timetable.mine'), 'schedule', sendMail: true)));
    }
}
