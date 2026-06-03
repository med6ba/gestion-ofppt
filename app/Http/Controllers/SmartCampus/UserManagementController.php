<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffUserRequest;
use App\Models\Group;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\RiskScoreService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();
        $search = $request->string('search')->toString();
        $validRoles = [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT, User::ROLE_FORMATEUR, User::ROLE_STAGIAIRE];

        $users = User::query()
            ->with(['group', 'riskScore'])
            ->when(in_array($role, $validRoles, true), fn ($query) => $query->where('role', $role))
            ->when($status && $role === User::ROLE_STAGIAIRE, fn ($query) => $query->where('approval_status', $status))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registration_number', 'like', "%{$search}%");
                });
            })
            ->orderByRaw("case role when 'directeur' then 1 when 'surveillant' then 2 when 'formateur' then 3 else 4 end")
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'filters' => [
                'role' => $role,
                'status' => $status,
                'search' => $search,
            ],
            'roleCounts' => User::query()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role'),
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
