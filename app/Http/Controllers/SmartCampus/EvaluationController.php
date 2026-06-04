<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Exports\EvaluationPvExport;
use App\Http\Requests\StoreEvaluationGradesRequest;
use App\Models\Evaluation;
use App\Models\GradeAuditLog;
use App\Models\Group;
use App\Models\ModuleGradeSummary;
use App\Models\StudentGrade;
use App\Models\TimetableSession;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\GradeCalculationService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class EvaluationController extends Controller
{
    public function index(Request $request, GradeCalculationService $grades): View
    {
        $user = $request->user();

        if ($user->isStagiaire()) {
            $summaries = $this->summaryRows($request, $user, $grades)
                ->where('status', ModuleGradeSummary::STATUS_PUBLISHED)
                ->values();

            return view('evaluations.my-notes', [
                'summaries' => $summaries,
            ]);
        }

        return view('evaluations.index', [
            'rows' => $this->summaryRows($request, $user, $grades),
            'groups' => $this->availableGroups($user),
            'modules' => $this->availableModules($user),
            'formateurs' => $this->availableFormateurs($user),
            'types' => $this->typeLabels(),
            'statuses' => $this->statusLabels(),
            'filters' => $request->only(['group_id', 'module_id', 'formateur_id', 'type', 'status', 'search']),
        ]);
    }

    public function gradeEntry(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isFormateur(), 403);

        $pairs = $this->teachingPairs($user);
        $selected = $this->selectedPair($request, $pairs);
        $date = $request->query('evaluation_date', now()->toDateString());
        $students = collect();
        $evaluations = collect();
        $grades = collect();
        $activeTypes = [];

        if ($selected) {
            $activeTypes = $selected['module']->evaluationTypes();
            $students = User::query()
                ->role(User::ROLE_STAGIAIRE)
                ->approved()
                ->where('group_id', $selected['group']->id)
                ->orderBy('name')
                ->get();

            $evaluations = Evaluation::with('grades')
                ->where('group_id', $selected['group']->id)
                ->where('module_id', $selected['module']->id)
                ->whereIn('type', $activeTypes)
                ->get()
                ->keyBy('type');

            $grades = $evaluations->map(fn (Evaluation $evaluation) => $evaluation->grades->keyBy('stagiaire_id'));
        }

        $enteredCount = $students->sum(function (User $student) use ($grades, $activeTypes) {
            return collect($activeTypes)->sum(function (string $type) use ($student, $grades) {
                $grade = $grades->get($type)?->get($student->id);

                return $grade && ($grade->absent || $grade->score !== null) ? 1 : 0;
            });
        });
        $expectedCount = $students->count() * count($activeTypes);

        return view('evaluations.grade-entry', [
            'pairs' => $pairs,
            'selected' => $selected,
            'typeLabels' => $this->typeLabels(),
            'date' => $date,
            'students' => $students,
            'evaluations' => $evaluations,
            'grades' => $grades,
            'enteredCount' => $enteredCount,
            'expectedCount' => $expectedCount,
            'activeTypes' => $activeTypes,
        ]);
    }

    public function storeGrades(StoreEvaluationGradesRequest $request, GradeCalculationService $gradeCalculation): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $groupId = (int) $data['group_id'];
        $moduleId = (int) $data['module_id'];

        abort_unless($this->canManagePair($user, $groupId, $moduleId), 403);

        $module = TrainingModule::findOrFail($moduleId);
        $activeTypes = $module->evaluationTypes();

        $validStudentIds = User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->where('group_id', $groupId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $existingEvaluations = Evaluation::with('grades')
            ->where('group_id', $groupId)
            ->where('module_id', $moduleId)
            ->whereIn('type', $activeTypes)
            ->get()
            ->keyBy('type');
        $hasPublishedGrades = $existingEvaluations->contains(fn (Evaluation $evaluation) => $evaluation->isPublished());

        if ($hasPublishedGrades && $this->publishedGradesWillChange($existingEvaluations, $data['grades'], $activeTypes) && blank($data['reason'] ?? null)) {
            return back()
                ->withErrors(['reason' => 'Un motif est obligatoire pour corriger des notes deja publiees.'])
                ->withInput();
        }

        DB::transaction(function () use ($data, $user, $groupId, $module, $moduleId, $validStudentIds, $existingEvaluations, $activeTypes, $hasPublishedGrades, $gradeCalculation) {
            $status = ($data['action'] === 'publish' || $hasPublishedGrades)
                ? Evaluation::STATUS_PUBLISHED
                : Evaluation::STATUS_DRAFT;
            $changedPublishedGrades = collect();
            $savedEvaluations = collect();

            foreach ($activeTypes as $type) {
                $existingEvaluation = $existingEvaluations->get($type);
                $wasTypePublished = $existingEvaluation?->isPublished() === true;
                $evaluation = Evaluation::updateOrCreate(
                    [
                        'group_id' => $groupId,
                        'module_id' => $moduleId,
                        'type' => $type,
                    ],
                    [
                        'formateur_id' => $user->id,
                        'title' => strtoupper($type).' - '.$module->name,
                        'coefficient' => null,
                        'max_score' => Evaluation::maxScoreFor($type),
                        'evaluation_date' => $data['evaluation_date'],
                        'status' => $status,
                        'created_by' => $existingEvaluation?->created_by ?? $user->id,
                        'published_at' => $status === Evaluation::STATUS_PUBLISHED ? ($existingEvaluation?->published_at ?? now()) : null,
                    ]
                );
                $existingGrades = $evaluation->grades()->get()->keyBy('stagiaire_id');

                foreach ($data['grades'] as $studentId => $studentGradeData) {
                    $studentId = (int) $studentId;

                    if (!in_array($studentId, $validStudentIds, true)) {
                        continue;
                    }

                    $gradeData = $studentGradeData[$type] ?? [];
                    $absent = $type === Evaluation::TYPE_EFM && filter_var($gradeData['absent'] ?? false, FILTER_VALIDATE_BOOL);
                    $rawScore = $gradeData['score'] ?? null;
                    $score = $absent || $rawScore === null || $rawScore === '' ? null : round((float) $rawScore, 2);
                    $observation = $absent ? 'Absent' : ($gradeData['observation'] ?? null);
                    $oldGrade = $existingGrades->get($studentId);
                    $changed = !$oldGrade
                        || ($this->scoreValue($oldGrade->score) !== $this->scoreValue($score)
                            || (bool) $oldGrade->absent !== $absent
                            || (string) ($oldGrade->observation ?? '') !== (string) ($observation ?? ''));

                    $studentGrade = StudentGrade::updateOrCreate(
                        [
                            'evaluation_id' => $evaluation->id,
                            'stagiaire_id' => $studentId,
                        ],
                        [
                            'score' => $score,
                            'absent' => $absent,
                            'observation' => $observation,
                        ]
                    );

                    if ($wasTypePublished && $changed) {
                        GradeAuditLog::create([
                            'student_grade_id' => $studentGrade->id,
                            'old_score' => $oldGrade?->score,
                            'new_score' => $score,
                            'changed_by' => $user->id,
                            'reason' => $data['reason'],
                            'created_at' => now(),
                        ]);

                        $changedPublishedGrades->push($studentGrade);
                    }
                }

                $savedEvaluations->push($evaluation);
            }

            if ($status === Evaluation::STATUS_PUBLISHED) {
                $summaries = $gradeCalculation->refreshForEvaluation($savedEvaluations->first());
                $moduleName = $module->name;

                if (!$hasPublishedGrades) {
                    User::query()
                        ->whereIn('id', $validStudentIds)
                        ->get()
                        ->each(fn (User $student) => $student->notify(new SmartCampusNotification(
                            'Notes publiees',
                            "Vos notes du module $moduleName sont disponibles.",
                            route('evaluations.index'),
                            'evaluations'
                        )));
                }

                $changedPublishedGrades->each(fn (StudentGrade $grade) => $grade->stagiaire?->notify(new SmartCampusNotification(
                    'Note corrigee',
                    "Une note du module $moduleName a ete corrigee.",
                    route('evaluations.index'),
                    'evaluations'
                )));

                $summaries
                    ->filter(fn (ModuleGradeSummary $summary) => $summary->moy_module !== null && (float) $summary->moy_module < 10)
                    ->each(fn (ModuleGradeSummary $summary) => $summary->stagiaire?->notify(new SmartCampusNotification(
                        'Risque academique',
                        "Votre moyenne du module $moduleName est inferieure a 10.",
                        route('evaluations.index'),
                        'evaluations'
                    )));
            } else {
                $gradeCalculation->refreshDraftForEvaluation($savedEvaluations->first());
            }
        });

        return redirect()
            ->route('evaluations.grades', ['group_id' => $groupId, 'module_id' => $moduleId, 'evaluation_date' => $data['evaluation_date']])
            ->with('status', $data['action'] === 'publish' ? 'Notes publiees.' : 'Brouillon enregistre.');
    }

    public function statistics(Request $request, GradeCalculationService $grades): View
    {
        $rows = $this->summaryRows($request->merge(['status' => ModuleGradeSummary::STATUS_PUBLISHED]), $request->user(), $grades)
            ->filter(fn (ModuleGradeSummary $row) => $row->moy_module !== null)
            ->values();

        $moduleAverages = $rows
            ->groupBy(fn (ModuleGradeSummary $row) => $row->module?->name ?? 'Module')
            ->map(fn (Collection $items, string $label) => ['label' => $label, 'value' => round($items->avg('moy_module'), 2)])
            ->values();
        $groupSuccess = $rows
            ->groupBy(fn (ModuleGradeSummary $row) => $row->group?->code ?? 'Groupe')
            ->map(function (Collection $items, string $label) {
                $complete = $items->filter->isComplete();

                return [
                    'label' => $label,
                    'value' => $complete->count() ? round(($complete->filter(fn ($row) => (float) $row->moy_module >= 10)->count() / $complete->count()) * 100, 1) : 0,
                ];
            })
            ->values();
        $belowTen = $rows->filter(fn (ModuleGradeSummary $row) => (float) $row->moy_module < 10);
        $distribution = collect([
            ['label' => '0-5', 'value' => $rows->filter(fn ($row) => (float) $row->moy_module < 5)->count()],
            ['label' => '5-10', 'value' => $rows->filter(fn ($row) => (float) $row->moy_module >= 5 && (float) $row->moy_module < 10)->count()],
            ['label' => '10-14', 'value' => $rows->filter(fn ($row) => (float) $row->moy_module >= 10 && (float) $row->moy_module < 14)->count()],
            ['label' => '14-20', 'value' => $rows->filter(fn ($row) => (float) $row->moy_module >= 14)->count()],
        ]);

        return view('evaluations.statistics', [
            'rows' => $rows,
            'moduleAverages' => $moduleAverages,
            'groupSuccess' => $groupSuccess,
            'distribution' => $distribution,
            'belowTen' => $belowTen,
            'globalAverage' => $rows->count() ? round($rows->avg('moy_module'), 2) : null,
            'bestModule' => $moduleAverages->sortByDesc('value')->first(),
            'weakestModule' => $moduleAverages->sortBy('value')->first(),
            'filters' => $request->only(['group_id', 'module_id', 'formateur_id']),
            'groups' => $this->availableGroups($request->user()),
            'modules' => $this->availableModules($request->user()),
            'formateurs' => $this->availableFormateurs($request->user()),
        ]);
    }

    public function exportExcel(Request $request, GradeCalculationService $grades)
    {
        abort_if($request->user()->isStagiaire(), 403);

        $rows = $this->summaryRows($request, $request->user(), $grades);
        $filename = 'pv-notes-'.now()->format('Ymd-His').'.xlsx';

        return Excel::download(new EvaluationPvExport($rows, $this->exportMeta($request, $rows)), $filename);
    }

    public function exportPdf(Request $request, GradeCalculationService $grades)
    {
        abort_if($request->user()->isStagiaire(), 403);

        $rows = $this->summaryRows($request, $request->user(), $grades);
        $html = view('evaluations.exports.pv', [
            'rows' => $rows,
            'meta' => $this->exportMeta($request, $rows),
            'logoDataUri' => $this->logoDataUri(),
        ])->render();
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="pv-notes-'.now()->format('Ymd-His').'.pdf"',
        ]);
    }

    private function summaryRows(Request $request, User $user, GradeCalculationService $grades): Collection
    {
        $query = ModuleGradeSummary::with(['stagiaire.group', 'group.filiere', 'module', 'formateur'])
            ->orderBy('group_id')
            ->orderBy('module_id')
            ->orderBy('stagiaire_id');

        if ($user->isStagiaire()) {
            $query->where('stagiaire_id', $user->id)->where('status', ModuleGradeSummary::STATUS_PUBLISHED);
        } elseif ($user->isFormateur()) {
            $query->where('formateur_id', $user->id);
        }

        foreach (['group_id', 'module_id', 'formateur_id', 'status'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('stagiaire', function ($studentQuery) use ($search) {
                $studentQuery->where('name', 'like', "%$search%")
                    ->orWhere('registration_number', 'like', "%$search%");
            });
        }

        $rows = $query->get();

        if ($request->filled('type')) {
            $allowedKeys = Evaluation::query()
                ->where('type', $request->input('type'))
                ->when($user->isFormateur(), fn ($evaluationQuery) => $evaluationQuery->where('formateur_id', $user->id))
                ->get(['group_id', 'module_id', 'formateur_id'])
                ->map(fn (Evaluation $evaluation) => $this->summaryKey($evaluation->group_id, $evaluation->module_id, $evaluation->formateur_id))
                ->all();

            $rows = $rows->filter(fn (ModuleGradeSummary $row) => in_array($this->summaryKey($row->group_id, $row->module_id, $row->formateur_id), $allowedKeys, true))->values();
        }

        return $rows->each(function (ModuleGradeSummary $summary) use ($grades) {
            $summary->setAttribute('observations_text', $grades->observationsFor($summary->stagiaire_id, $summary->group_id, $summary->module_id));
        });
    }

    private function selectedPair(Request $request, Collection $pairs): ?array
    {
        if ($pairs->isEmpty()) {
            return null;
        }

        if ($request->filled('pair')) {
            [$pairGroupId, $pairModuleId] = array_pad(explode('-', (string) $request->query('pair'), 2), 2, null);

            return $pairs->first(fn (array $pair) => (int) $pair['group']->id === (int) $pairGroupId && (int) $pair['module']->id === (int) $pairModuleId);
        }

        $groupId = $request->integer('group_id');
        $moduleId = $request->integer('module_id');

        if (!$groupId || !$moduleId) {
            return $pairs->first();
        }

        return $pairs->first(fn (array $pair) => (int) $pair['group']->id === $groupId && (int) $pair['module']->id === $moduleId);
    }

    private function teachingPairs(User $user): Collection
    {
        $pairs = collect();
        $modules = TrainingModule::query()->get()->keyBy('id');

        $user->teachingGroups()->with('filiere')->get()->each(function (Group $group) use ($modules, $pairs) {
            $module = $modules->get($group->pivot->module_id);

            if ($module) {
                $pairs->push(['group' => $group, 'module' => $module]);
            }
        });

        TimetableSession::with(['group.filiere', 'module'])
            ->where('formateur_id', $user->id)
            ->get()
            ->each(function (TimetableSession $session) use ($pairs) {
                if ($session->group && $session->module) {
                    $pairs->push(['group' => $session->group, 'module' => $session->module]);
                }
            });

        return $pairs
            ->unique(fn (array $pair) => $pair['group']->id.'-'.$pair['module']->id)
            ->sortBy(fn (array $pair) => $pair['group']->code.' '.$pair['module']->name)
            ->values();
    }

    private function canManagePair(User $user, int $groupId, int $moduleId): bool
    {
        return $this->teachingPairs($user)
            ->contains(fn (array $pair) => (int) $pair['group']->id === $groupId && (int) $pair['module']->id === $moduleId);
    }

    private function availableGroups(User $user): Collection
    {
        if ($user->isFormateur()) {
            return $this->teachingPairs($user)->pluck('group')->unique('id')->values();
        }

        return Group::with('filiere')->orderBy('code')->get();
    }

    private function availableModules(User $user): Collection
    {
        if ($user->isFormateur()) {
            return $this->teachingPairs($user)->pluck('module')->unique('id')->values();
        }

        return TrainingModule::orderBy('name')->get();
    }

    private function availableFormateurs(User $user): Collection
    {
        if ($user->isFormateur()) {
            return collect([$user]);
        }

        return User::role(User::ROLE_FORMATEUR)->orderBy('name')->get();
    }

    private function publishedGradesWillChange(Collection $evaluations, array $grades, array $activeTypes): bool
    {
        $existingByType = $evaluations->map(fn (Evaluation $evaluation) => $evaluation->grades->keyBy('stagiaire_id'));

        foreach ($activeTypes as $type) {
            $evaluation = $evaluations->get($type);

            if (!$evaluation?->isPublished()) {
                continue;
            }

            foreach ($grades as $studentId => $studentGradeData) {
                $oldGrade = $existingByType->get($type)?->get((int) $studentId);
                $gradeData = $studentGradeData[$type] ?? [];
                $absent = $type === Evaluation::TYPE_EFM && filter_var($gradeData['absent'] ?? false, FILTER_VALIDATE_BOOL);
                $rawScore = $gradeData['score'] ?? null;
                $score = $absent || $rawScore === null || $rawScore === '' ? null : round((float) $rawScore, 2);
                $observation = $absent ? 'Absent' : ($gradeData['observation'] ?? null);

                if (!$oldGrade
                    || $this->scoreValue($oldGrade->score) !== $this->scoreValue($score)
                    || (bool) $oldGrade->absent !== $absent
                    || (string) ($oldGrade->observation ?? '') !== (string) ($observation ?? '')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function scoreValue(mixed $score): ?string
    {
        return $score === null || $score === '' ? null : number_format((float) $score, 2, '.', '');
    }

    private function typeLabels(): array
    {
        return [
            Evaluation::TYPE_CC1 => 'CC1',
            Evaluation::TYPE_CC2 => 'CC2',
            Evaluation::TYPE_CC3 => 'CC3',
            Evaluation::TYPE_EFM => 'EFM',
        ];
    }

    private function statusLabels(): array
    {
        return [
            ModuleGradeSummary::STATUS_DRAFT => 'Brouillon',
            ModuleGradeSummary::STATUS_PUBLISHED => 'Publie',
        ];
    }

    private function summaryKey(int $groupId, int $moduleId, int $formateurId): string
    {
        return "$groupId-$moduleId-$formateurId";
    }

    private function exportMeta(Request $request, Collection $rows): array
    {
        $first = $rows->first();

        return [
            'etablissement' => 'Smart Campus OFPPT',
            'filiere' => $first?->group?->filiere?->name ?? 'Toutes les filieres',
            'groupe' => $first?->group?->code ?? 'Tous les groupes',
            'niveau' => $first?->group?->year_level ?? '',
            'annee' => now()->year,
            'module' => $first?->module?->name ?? 'Tous les modules',
            'inscrits' => $rows->count(),
            'presents' => $rows->filter(fn (ModuleGradeSummary $row) => filled($row->efm))->count(),
            'absents' => $rows->filter(fn (ModuleGradeSummary $row) => str_contains((string) $row->getAttribute('observations_text'), 'Absent'))->count(),
            'date' => now()->format('d/m/Y'),
        ];
    }

    private function logoDataUri(): ?string
    {
        $path = public_path('logo/ofppt-logo.png');

        if (!is_file($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(file_get_contents($path));
    }
}
