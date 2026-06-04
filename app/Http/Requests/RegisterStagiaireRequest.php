<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterStagiaireRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'cni' => ['required', 'string', 'max:40', 'unique:users,cni'],
            'group_id' => ['required', 'exists:groups,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
