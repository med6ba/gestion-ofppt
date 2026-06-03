<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('attendance.session.{sessionId}', function ($user, $sessionId) {
    // Only the teacher (formateur) of this session should be able to listen
    $session = \App\Models\TimetableSession::find($sessionId);
    if (!$session) {
        return false;
    }
    return (int) $user->id === (int) $session->formateur_id;
});

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});
