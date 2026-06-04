<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\User;
use App\Models\TimetableSession;
use App\Models\Group;

Broadcast::channel('user.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.User.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('dashboard.{id}', function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('role.{role}', function (User $user, $role) {
    return $user->role === $role;
});

Broadcast::channel('chat.{conversationId}', function (User $user, $conversationId) {
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('group.{groupId}', function (User $user, $groupId) {
    if ($user->isDirecteur() || $user->isSurveillant()) {
        return true;
    }
    if ($user->isFormateur()) {
        return $user->teachingGroups()->where('groups.id', $groupId)->exists();
    }
    return (int) $user->group_id === (int) $groupId;
});

Broadcast::channel('timetable.group.{groupId}', function (User $user, $groupId) {
    if ($user->isDirecteur() || $user->isSurveillant()) {
        return true;
    }
    if ($user->isFormateur()) {
        return $user->teachingGroups()->where('groups.id', $groupId)->exists();
    }
    return (int) $user->group_id === (int) $groupId;
});

Broadcast::channel('attendance.session.{sessionId}', function (User $user, $sessionId) {
    if ($user->isDirecteur() || $user->isSurveillant()) {
        return true;
    }
    
    $session = TimetableSession::find($sessionId);
    if (!$session) {
        return false;
    }
    
    if ($user->isFormateur()) {
        return (int) $user->id === (int) $session->formateur_id;
    }
    
    // If student, check if they are part of the group for this session
    return (int) $user->group_id === (int) $session->group_id;
});
