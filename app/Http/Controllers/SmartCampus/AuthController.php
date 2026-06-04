<?php

namespace App\Http\Controllers\SmartCampus;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterStagiaireRequest;
use App\Models\Group;
use App\Models\User;
use App\Notifications\SmartCampusNotification;
use App\Services\SafeNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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
            return back()->withErrors(['email' => __('messages.auth.invalid_credentials')])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (!$user->enabled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => __('messages.auth.account_disabled')]);
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

            return back()->withErrors(['email' => __('messages.auth.registration_rejected')]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function showRegister(): View
    {
        return view('auth.register', [
            'groups' => Group::query()->with('filiere')->orderBy('code')->get(),
        ]);
    }

    public function register(RegisterStagiaireRequest $request, SafeNotificationService $notifier): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'password' => Hash::make($request->string('password')),
            'role' => User::ROLE_STAGIAIRE,
            'group_id' => $request->integer('group_id'),
            'phone' => $request->string('phone') ?: null,
            'registration_number' => $request->string('registration_number') ?: null,
            'cni' => Str::upper($request->string('cni')->trim()->toString()),
            'approval_status' => 'pending',
        ]);

        $notifier->send($user, new SmartCampusNotification(
            __('messages.mail.account_created_title'),
            __('messages.mail.account_created_body', ['email' => $user->email]),
            route('login'),
            'account',
            sendMail: true
        ));

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

    public function qrLogin(string $token, Request $request): RedirectResponse|View
    {
        $user = User::query()
            ->where('qr_login_token', $token)
            ->first();

        if (!$user || !$user->isStagiaire()) {
            return view('auth.qr-error', [
                'title' => __('messages.auth.qr_error_title'),
                'message' => __('messages.auth.qr_error_message'),
            ]);
        }

        if (!$user->enabled) {
            return view('auth.qr-error', [
                'title' => __('messages.auth.qr_disabled_title'),
                'message' => __('messages.auth.qr_disabled_message'),
            ]);
        }

        if (!$user->isApproved()) {
            return view('auth.qr-error', [
                'title' => __('messages.auth.qr_unapproved_title'),
                'message' => __('messages.auth.qr_unapproved_message'),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('stagiaire.dashboard')->with('status', __('messages.auth.qr_success'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
