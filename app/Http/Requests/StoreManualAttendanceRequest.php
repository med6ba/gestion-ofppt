<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'attendance.*' => ['required', 'in:present,absent,late,justified'],
        ];
    }
}
