<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublishedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(): View
    {
        return view('announcements.index', [
            'announcements' => Announcement::with('sender')
                ->latest('sent_at')
                ->paginate(10),
            'canPublish' => auth()->user()->hasRole([User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->hasRole([User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT]), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'body' => ['required', 'string', 'min:10', 'max:3000'],
        ]);

        $recipients = User::query()
            ->role([User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE])
            ->approved()
            ->where('enabled', true)
            ->whereNotNull('email')
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        $announcement = Announcement::create([
            'sender_id' => $user->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'recipient_count' => $recipients->count(),
            'sent_at' => now(),
        ]);

        $announcement->load('sender');

        $recipients->each(function (User $recipient) use ($announcement) {
            $recipient->notify(
                (new AnnouncementPublishedNotification($announcement))
                    ->locale($recipient->preferred_locale ?: app()->getLocale())
            );
        });

        return redirect()
            ->route('announcements.index')
            ->with('status', __('messages.announcements.sent', ['count' => $recipients->count()]));
    }
}
