<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RiskScoreService;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user, RiskScoreService $riskScores): View
    {
        $viewer = auth()->user();

        abort_unless(
            $viewer->id === $user->id
            || $viewer->isDirecteur()
            || $viewer->isSurveillant()
            || ($viewer->isFormateur() && $user->isStagiaire() && $viewer->teachingGroups()->whereKey($user->group_id)->exists()),
            403
        );

        if ($user->isStagiaire()) {
            $riskScores->updateFor($user);
        }

        return view('profile.show', [
            'profile' => $user->load(['group.filiere', 'riskScore', 'attendances.session.module', 'attendanceAttempts']),
        ]);
    }
}
