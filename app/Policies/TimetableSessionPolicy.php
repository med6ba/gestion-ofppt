<?php

namespace App\Policies;

use App\Models\TimetableSession;
use App\Models\User;

class TimetableSessionPolicy
{
    public function manage(User $user): bool
    {
        return $user->isSurveillant();
    }

    public function markAttendance(User $user, TimetableSession $session): bool
    {
        return $user->isFormateur() && $session->formateur_id === $user->id;
    }

    public function view(User $user, TimetableSession $session): bool
    {
        if ($user->isDirecteur() || $user->isSurveillant()) {
            return true;
        }

        if ($user->isFormateur()) {
            return $session->formateur_id === $user->id;
        }

        return $user->isStagiaire() && $session->group_id === $user->group_id;
    }
}
