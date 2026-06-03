<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterStagiaireRequest;
use App\Models\Group;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (!$user->enabled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'This account is disabled.']);
        }

        if ($user->isStagiaire() && $user->approval_status === 'pending') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('approval.pending');
        }

        if ($user->isStagiaire() && $user->approval_status === 'rejected') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Your registration was rejected. Please contact administration.']);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function passkeyStart(): RedirectResponse
    {
        return back()->with('status', 'Passkey support is ready in the architecture. Please use email/password until a passkey is registered.');
    }

    public function showRegister(): View
    {
        return view('auth.register', [
            'groups' => Group::query()->with('filiere')->orderBy('code')->get(),
        ]);
    }

    public function register(RegisterStagiaireRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $request->integer('group_id'),
            'phone' => $request->string('phone') ?: null,
            'registration_number' => $request->string('registration_number') ?: null,
            'approval_status' => 'pending',
        ]);

        User::query()
            ->whereIn('role', [User::ROLE_DIRECTEUR, User::ROLE_SURVEILLANT])
            ->get()
            ->each(fn (User $admin) => $admin->notify(new SmartCampusNotification(
                'New stagiaire pending approval',
                "{$user->name} registered and is waiting for approval.",
                route('users.index'),
                'approval'
            )));

        return redirect()->route('approval.pending');
    }

    public function pending(): View
    {
        return view('auth.pending');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
