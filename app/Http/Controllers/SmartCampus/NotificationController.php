<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(): View
    {
        return view('notifications.index', [
            'notifications' => auth()->user()->notifications()->paginate(20),
        ]);
    }

    public function markRead(string $notification): RedirectResponse
    {
        $record = auth()->user()->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back();
    }
}
