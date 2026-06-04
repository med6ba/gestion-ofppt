<?php

namespace App\Services;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SafeNotificationService
{
    public function send(object $notifiable, Notification $notification): void
    {
        try {
            $notifiable->notify($notification);
        } catch (\Throwable $exception) {
            Log::warning('Smart Campus notification failed.', [
                'notifiable_type' => $notifiable::class,
                'notifiable_id' => $notifiable->id ?? null,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
