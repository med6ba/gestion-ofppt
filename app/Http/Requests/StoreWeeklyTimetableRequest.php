<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class StoreWeeklyTimetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isSurveillant();
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'week_start_date' => [
                'required', 'date',
                function ($attr, $value, $fail) {
                    $date = Carbon::parse($value);
                    if ($date->dayOfWeekIso !== 1) {
                        $fail('La date de début doit être un lundi.');
                    }
                },
                Rule::unique('weekly_timetables')->where(fn ($q) => $q->where('group_id', $this->group_id)),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_start_date.unique' => 'Un emploi du temps existe déjà pour ce groupe pendant cette semaine.',
            'group_id.required' => 'Le groupe est obligatoire.',
            'week_start_date.required' => 'La date de début de semaine est obligatoire.',
        ];
    }
}
