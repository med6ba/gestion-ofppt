<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isFormateur() === true;
    }

    public function rules(): array
    {
        return [
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', Rule::in(Attendance::statuses())],
        ];
    }
}
