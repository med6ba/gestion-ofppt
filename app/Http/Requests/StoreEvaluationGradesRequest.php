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
            'reason' => ['nullable', 'string', 'max:255'],
            'grades' => ['required', 'array'],
            'grades.*' => ['array'],
            'grades.*.*.score' => ['nullable', 'numeric', 'min:0'],
            'grades.*.*.absent' => ['nullable', 'boolean'],
            'grades.*.*.observation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
