<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimetableSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSurveillant() === true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['required', 'exists:groups,id'],
            'module_id' => ['required', 'exists:modules,id'],
            'formateur_id' => ['required', 'exists:users,id'],
            'room_id' => ['required', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', 'between:1,6'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'week_number' => ['nullable', 'integer', 'between:1,53'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'change_note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
