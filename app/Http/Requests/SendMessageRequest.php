<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Setting;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $maxSizeMB = Setting::get('max_chat_attachment_mb', 5);
        $maxSizeKB = $maxSizeMB * 1024;

        return [
            'body' => ['required_without:attachment', 'nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:' . $maxSizeKB],
        ];
    }
}
