<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SmartCampusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $title,
        private string $body,
        private ?string $url = null,
        private string $category = 'info',
        private bool $sendMail = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendMail && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'category' => $this->category,
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->title)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->body);

        if ($this->url) {
            $message->action('Voir l\'emploi du temps', $this->url);
        }

        return $message->line('Smart Campus OFPPT');
    }
}
