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
        return [
            'body' => ['required_without:attachment', 'nullable', 'string', 'max:4000'],
            'attachment' => ['nullable', 'file'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('attachment')) {
                $file = $this->file('attachment');
                $ext = strtolower($file->getClientOriginalExtension());
                
                $categories = [
                    'image' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                    'video' => ['mp4', 'webm', 'mov'],
                    'pdf' => ['pdf'],
                    'word' => ['doc', 'docx'],
                    'powerpoint' => ['ppt', 'pptx'],
                    'excel' => ['xls', 'xlsx', 'csv'],
                ];

                $category = 'other';
                foreach ($categories as $cat => $extensions) {
                    if (in_array($ext, $extensions)) {
                        $category = $cat;
                        break;
                    }
                }

                if ($category === 'other') {
                    $validator->errors()->add('attachment', 'Type de fichier non autorisé.');
                    return;
                }

                // Check permissions
                if (!Setting::get("enable_{$category}_attachments", true)) {
                    $validator->errors()->add('attachment', "L'envoi de fichiers de type {$category} est désactivé.");
                    return;
                }

                // Check size limits
                $sizeKey = match ($category) {
                    'image' => 'max_chat_image_size_mb',
                    'video' => 'max_chat_video_size_mb',
                    default => 'max_chat_document_size_mb',
                };
                $defaultSize = match ($category) {
                    'image' => 5,
                    'video' => 25,
                    default => 10,
                };
                
                $maxSizeMB = Setting::get($sizeKey, $defaultSize);
                $maxSizeKB = $maxSizeMB * 1024;
                
                if ($file->getSize() > $maxSizeKB * 1024) { // getSize is in bytes
                    $validator->errors()->add('attachment', "La taille du fichier ne doit pas dépasser {$maxSizeMB} MB.");
                }
            }
        });
    }
}
