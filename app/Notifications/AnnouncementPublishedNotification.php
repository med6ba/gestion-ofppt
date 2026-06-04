<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublishedNotification extends Notification
{
    use Queueable;

    public function __construct(private Announcement $announcement)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return new DatabaseMessage([
            'title' => $this->announcement->title,
            'body' => $this->announcement->body,
            'url' => route('announcements.index'),
            'category' => 'announcements',
        ]);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sender = $this->announcement->sender;
        $senderName = $sender?->name ?? __('messages.announcements.system_sender');
        $senderRole = $sender?->roleLabel() ?? __('messages.announcements.administration');
        $sentAt = $this->announcement->sent_at?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i');

        return (new MailMessage)
            ->subject(__('messages.mail.announcement_subject', ['title' => $this->announcement->title]))
            ->greeting(__('messages.mail.greeting', ['name' => $notifiable->name]))
            ->line(__('messages.mail.announcement_intro', ['name' => $senderName, 'role' => $senderRole]))
            ->line(__('messages.mail.announcement_sent_at', ['time' => $sentAt]))
            ->line($this->announcement->title)
            ->line($this->announcement->body)
            ->action(__('messages.mail.action'), route('announcements.index'))
            ->line(__('messages.mail.footer'));
    }
}
