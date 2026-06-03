<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffUserRequest;
use App\Models\Group;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\RiskScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'pendingStagiaires' => User::with('group')->role(User::ROLE_STAGIAIRE)->where('approval_status', 'pending')->latest()->get(),
            'staff' => User::role([User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR])->orderBy('role')->orderBy('name')->get(),
            'stagiaires' => User::with(['group', 'riskScore'])->role(User::ROLE_STAGIAIRE)->orderBy('name')->get(),
            'groups' => Group::orderBy('code')->get(),
        ]);
    }

    public function storeStaff(StoreStaffUserRequest $request): RedirectResponse
    {
        User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->string('phone') ?: null,
            'role' => $request->string('role'),
            'approval_status' => 'approved',
            'password' => Hash::make($request->string('password')),
        ])->notify(new SmartCampusNotification(
            'Account created',
            'Your Smart Campus OFPPT account is ready.',
            route('login'),
            'account'
        ));

        return back()->with('status', 'Staff account created.');
    }

    public function approve(User $user, RiskScoreService $riskScoreService): RedirectResponse
    {
        abort_unless($user->isStagiaire(), 404);

        $user->update(['approval_status' => 'approved']);
        $riskScoreService->updateFor($user);

        $user->notify(new SmartCampusNotification(
            'Registration approved',
            'Your Smart Campus OFPPT account has been approved.',
            route('stagiaire.dashboard'),
            'approval'
        ));

        return back()->with('status', "{$user->name} approved.");
    }

    public function reject(User $user): RedirectResponse
    {
        abort_unless($user->isStagiaire(), 404);

        $user->update(['approval_status' => 'rejected']);
        $user->notify(new SmartCampusNotification(
            'Registration rejected',
            'Your Smart Campus OFPPT registration was rejected. Please contact administration.',
            null,
            'approval'
        ));

        return back()->with('status', "{$user->name} rejected.");
    }
}
