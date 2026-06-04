<?php

namespace App\Services;

use App\Models\Evaluation;
use App\Models\ModuleGradeSummary;
use App\Models\StudentGrade;
use App\Models\TrainingModule;
use App\Models\User;
use Illuminate\Support\Collection;

class GradeCalculationService
{
    public function refreshForStudent(int $stagiaireId, int $groupId, int $moduleId): ?ModuleGradeSummary
    {
        return $this->refreshForStudentUsingEvaluations($stagiaireId, $groupId, $moduleId, Evaluation::STATUS_PUBLISHED, ModuleGradeSummary::STATUS_PUBLISHED);
    }

    public function refreshDraftForEvaluation(Evaluation $evaluation): Collection
    {
        $stagiaireIds = User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->where('group_id', $evaluation->group_id)
            ->pluck('id');

        return $stagiaireIds
            ->map(function (int $stagiaireId) use ($evaluation) {
                $existing = ModuleGradeSummary::query()
                    ->where('stagiaire_id', $stagiaireId)
                    ->where('group_id', $evaluation->group_id)
                    ->where('module_id', $evaluation->module_id)
                    ->first();

                if ($existing?->status === ModuleGradeSummary::STATUS_PUBLISHED) {
                    return $existing;
                }

                return $this->refreshForStudentUsingEvaluations($stagiaireId, $evaluation->group_id, $evaluation->module_id, null, ModuleGradeSummary::STATUS_DRAFT);
            })
            ->filter()
            ->values();
    }

    private function refreshForStudentUsingEvaluations(int $stagiaireId, int $groupId, int $moduleId, ?string $evaluationStatus, string $summaryStatus): ?ModuleGradeSummary
    {
        $module = TrainingModule::find($moduleId);
        $activeTypes = $module?->evaluationTypes() ?? Evaluation::types();
        $activeCcTypes = $module?->ccTypes() ?? [Evaluation::TYPE_CC1, Evaluation::TYPE_CC2, Evaluation::TYPE_CC3];

        $evaluations = Evaluation::with('grades')
            ->where('group_id', $groupId)
            ->where('module_id', $moduleId)
            ->whereIn('type', $activeTypes)
            ->when($evaluationStatus, fn ($query) => $query->where('status', $evaluationStatus))
            ->get()
            ->keyBy('type');

        if ($evaluations->isEmpty()) {
            return null;
        }

        $scoreFor = function (string $type) use ($evaluations, $stagiaireId): ?float {
            $grade = $evaluations->get($type)?->grades->firstWhere('stagiaire_id', $stagiaireId);

            if (!$grade || $grade->absent || $grade->score === null) {
                return null;
            }

            return (float) $grade->score;
        };

        $cc1 = $scoreFor(Evaluation::TYPE_CC1);
        $cc2 = $scoreFor(Evaluation::TYPE_CC2);
        $cc3 = in_array(Evaluation::TYPE_CC3, $activeCcTypes, true) ? $scoreFor(Evaluation::TYPE_CC3) : null;
        $efm = $scoreFor(Evaluation::TYPE_EFM);

        $ccScores = collect($activeCcTypes)->map(fn (string $type) => $scoreFor($type))->filter(fn ($score) => $score !== null);
        $moyCc = $ccScores->isNotEmpty() ? round($ccScores->avg(), 2) : null;
        $moyModule = $moyCc !== null && $efm !== null ? round(($moyCc + $efm) / 3, 2) : null;
        $formateurId = $evaluations->first()?->formateur_id;

        if (!$formateurId) {
            return null;
        }

        return ModuleGradeSummary::updateOrCreate(
            [
                'stagiaire_id' => $stagiaireId,
                'group_id' => $groupId,
                'module_id' => $moduleId,
            ],
            [
                'formateur_id' => $formateurId,
                'cc1' => $cc1,
                'cc2' => $cc2,
                'cc3' => $cc3,
                'moy_cc' => $moyCc,
                'efm' => $efm,
                'moy_module' => $moyModule,
                'status' => $summaryStatus,
                'published_at' => $summaryStatus === ModuleGradeSummary::STATUS_PUBLISHED ? now() : null,
            ]
        );
    }

    public function refreshForEvaluation(Evaluation $evaluation): Collection
    {
        $stagiaireIds = User::query()
            ->role(User::ROLE_STAGIAIRE)
            ->approved()
            ->where('group_id', $evaluation->group_id)
            ->pluck('id');

        return $stagiaireIds
            ->map(fn (int $stagiaireId) => $this->refreshForStudent($stagiaireId, $evaluation->group_id, $evaluation->module_id))
            ->filter()
            ->values();
    }

    public function observationsFor(int $stagiaireId, int $groupId, int $moduleId): string
    {
        $module = TrainingModule::find($moduleId);
        $activeTypes = $module?->evaluationTypes() ?? Evaluation::types();

        return StudentGrade::query()
            ->where('stagiaire_id', $stagiaireId)
            ->where(function ($query) {
                $query->where('absent', true)->orWhereNotNull('observation');
            })
            ->whereHas('evaluation', function ($query) use ($groupId, $moduleId) {
                $query->where('group_id', $groupId)
                    ->where('module_id', $moduleId)
                    ->where('status', Evaluation::STATUS_PUBLISHED);
            })
            ->with('evaluation')
            ->get()
            ->filter(fn (StudentGrade $grade) => in_array($grade->evaluation->type, $activeTypes, true))
            ->map(function (StudentGrade $grade) {
                $prefix = strtoupper($grade->evaluation->type);

                return $prefix.': '.($grade->absent ? 'Absent' : $grade->observation);
            })
            ->filter()
            ->implode(' | ');
    }
}
