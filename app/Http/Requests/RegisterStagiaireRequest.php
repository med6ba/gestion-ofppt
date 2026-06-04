<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterStagiaireRequest extends FormRequest
{
    private const EMAIL_DOMAIN = '@ofppt-edu.ma';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $localPart = str($this->input('email_local_part', $this->input('email', '')))
            ->before('@')
            ->lower()
            ->replaceMatches('/[^a-z0-9._-]/', '')
            ->trim('.-_')
            ->toString();

        $this->merge([
            'email_local_part' => $localPart,
            'email' => $localPart.self::EMAIL_DOMAIN,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email_local_part' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/'],
            'email' => ['required', 'email', 'max:255', 'ends_with:'.self::EMAIL_DOMAIN, 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'cni' => ['required', 'string', 'max:40', 'unique:users,cni'],
            'group_id' => ['required', 'exists:groups,id'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function attributes(): array
    {
        return [
            'email_local_part' => __('messages.auth.email_local_part'),
        ];
    }
}
