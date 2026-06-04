<?php

namespace App\Http\Requests;

use App\Models\Evaluation;
use App\Models\TrainingModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEvaluationGradesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFormateur() === true;
    }

    protected function prepareForValidation(): void
    {
        $grades = $this->input('grades', []);

        foreach ($grades as $studentId => $studentGrades) {
            foreach ($studentGrades as $type => $grade) {
                if (!array_key_exists('score', $grade)) {
                    continue;
                }

                if (is_string($grade['score'])) {
                    $grade['score'] = str_replace(',', '.', trim($grade['score']));
                }

                $grades[$studentId][$type] = $grade;
            }
        }

        $this->merge(['grades' => $grades]);
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'integer', 'exists:groups,id'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'evaluation_date' => ['required', 'date'],
            'action' => ['required', Rule::in(['draft', 'publish'])],
            'reason' => ['nullable', 'string', 'max:255'],
            'grades' => ['required', 'array'],
            'grades.*' => ['array'],
            'grades.*.*.score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.*.absent' => ['nullable', 'boolean'],
            'grades.*.*.observation' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $module = TrainingModule::find($this->input('module_id'));
            $types = $module?->evaluationTypes() ?? Evaluation::types();
            $isPublish = $this->input('action') === 'publish';
            $missingCount = 0;

            foreach ($this->input('grades', []) as $studentId => $studentGrades) {
                foreach ($types as $type) {
                    $grade = $studentGrades[$type] ?? [];
                    $absent = filter_var($grade['absent'] ?? false, FILTER_VALIDATE_BOOL);
                    $score = $grade['score'] ?? null;
                    $maxScore = Evaluation::maxScoreFor($type);

                    if ($type !== Evaluation::TYPE_EFM && $absent) {
                        $validator->errors()->add("grades.$studentId.$type.absent", 'L’absence est réservée à l’EFM.');
                    }

                    if ($absent) {
                        continue;
                    }

                    if ($score === null || $score === '') {
                        if ($isPublish) {
                            // Add a field-level marker (for inline highlighting) with empty message
                            // We will show a single global message instead of one per field
                            $validator->errors()->add("grades.$studentId.$type.score", '');
                            $missingCount++;
                        }
                        continue;
                    }

                    if ((float) $score > $maxScore) {
                        $validator->errors()->add("grades.$studentId.$type.score", strtoupper($type)." ne peut pas dépasser $maxScore.");
                    }
                }
            }

            // Add a single consolidated message for missing grades on publish
            if ($isPublish && $missingCount > 0) {
                $validator->errors()->add('_publish_incomplete',
                    "Publication impossible : $missingCount note(s) manquante(s). Remplissez toutes les notes ou cochez « Absent EFM » avant de publier.");
            }
        });
    }
}
