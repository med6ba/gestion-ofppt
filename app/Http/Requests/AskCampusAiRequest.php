<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskCampusAiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:600'],
        ];
    }
}
