<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PresenceXpService;
use App\Services\RiskScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(User $user, RiskScoreService $riskScores, PresenceXpService $presenceXp): View
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
            $presenceXp->refreshFor($user);
        }

        return view('profile.show', [
            'profile' => $user->load(['group.filiere', 'riskScore', 'presenceProfile', 'attendances.session.module', 'attendanceAttempts']),
        ]);
    }

    public function update(User $user, Request $request): RedirectResponse
    {
        $viewer = $request->user();

        abort_unless($user->isStagiaire(), 404);
        abort_unless($viewer->id === $user->id || $viewer->isDirecteur() || $viewer->isSurveillant(), 403);

        $data = Validator::make($request->all(), [
            'cni' => ['required', 'string', 'max:40', 'unique:users,cni,'.$user->id],
        ])->validate();

        $user->update([
            'cni' => Str::upper(trim($data['cni'])),
        ]);

        return back()->with('status', __('messages.profile.cni_updated'));
    }
}
