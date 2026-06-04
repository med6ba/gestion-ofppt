<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#005b9f">
    <title>{{ __('messages.common.login') }} - {{ __('messages.brand') }}</title>
    <link rel="manifest" href="/manifest.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <main class="flex min-h-screen w-full flex-col lg:flex-row">
        <section class="relative hidden w-full overflow-hidden lg:flex lg:w-1/2 xl:w-[55%]">
            <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('images/campus/ofppt-lab-login.jpeg') }}" alt="OFPPT digital classroom">
            <div class="absolute inset-0 bg-slate-950/45"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>

            <div class="relative z-10 flex h-full w-full flex-col justify-between px-12 py-10">
                <a href="{{ route('login') }}" class="flex items-center gap-3">
                    <img class="h-16 w-16 object-contain drop-shadow-[0_0_18px_rgba(255,255,255,0.25)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                </a>

                <div class="max-w-xl pb-8">
                    <div class="mb-4 inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-white/90 backdrop-blur">
                        Smart Campus OFPPT
                    </div>
                    <h1 class="text-4xl font-bold leading-tight text-white">{{ __('messages.auth.hero_title') }}</h1>
                    <p class="mt-4 max-w-lg text-base leading-relaxed text-white/85">{{ __('messages.auth.hero_text') }}</p>
                </div>
            </div>
        </section>

        <section class="relative flex w-full min-h-screen flex-col items-center justify-center bg-white px-4 py-12 lg:min-h-0 lg:w-1/2 lg:px-8 xl:w-[45%]">
            <div class="fixed right-6 top-5 z-10">
                <x-language-switcher />
            </div>
            <div class="fixed left-6 top-5 z-10 lg:hidden">
                <a href="{{ route('login') }}" class="flex items-center gap-2">
                    <img class="h-12 w-12 object-contain drop-shadow-[0_0_18px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                    <span class="font-bold text-slate-700">Smart Campus OFPPT</span>
                </a>
            </div>

            <div class="w-full max-w-sm">
                <div class="mb-8 text-center lg:text-left">
                    <h2 class="text-2xl font-bold text-slate-700 sm:text-3xl">{{ __('messages.auth.login_title') }}</h2>
                    <p class="mt-2 text-sm text-slate-400">{{ __('messages.auth.login_subtitle') }}</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif

                <div class="rounded-2xl border border-primary/15 bg-primary/5 p-4" x-data="qrBadgeLoginScanner({
                    opening: @js(__('messages.auth.camera_opening')),
                    active: @js(__('messages.auth.camera_active')),
                    detected: @js(__('messages.auth.qr_detected')),
                    invalid: @js(__('messages.auth.qr_invalid')),
                    permission: @js(__('messages.auth.camera_permission_refused')),
                    noCamera: @js(__('messages.auth.no_camera')),
                    unavailable: @js(__('messages.auth.camera_unavailable')),
                })">
                    <div class="flex items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-white text-primary shadow-sm">
                            <x-ui.icon name="qr" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-bold text-slate-700">{{ __('messages.auth.qr_login_title') }}</div>
                            <div class="mt-1 text-xs leading-5 text-slate-500">{{ __('messages.auth.qr_login_text') }}</div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="sc-btn sc-btn-primary h-10 rounded-xl" @click="start()" x-show="!scanning && !starting">{{ __('messages.auth.scan_badge_qr') }}</button>
                                <button type="button" class="sc-btn sc-btn-secondary h-10 rounded-xl" @click="stop()" x-show="scanning || starting" x-cloak>{{ __('messages.auth.stop_camera') }}</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden rounded-xl border border-primary/10 bg-white p-2" x-show="scanning || starting" x-cloak>
                        <div :id="readerId" class="min-h-64 overflow-hidden rounded-lg bg-slate-950"></div>
                    </div>

                    <div class="mt-3 text-xs font-semibold text-primary" x-text="status" x-show="status" x-cloak></div>
                    <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-800" x-text="error" x-show="error" x-cloak></div>
                </div>

                <div class="my-5 flex items-center gap-3 text-xs font-semibold uppercase text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    <span>{{ __('messages.auth.email_password_login') }}</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="relative flex">
                            <input class="sc-input h-12 rounded-xl pl-4" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="{{ __('messages.auth.email_placeholder') }}" required autofocus autocomplete="email">
                        </label>
                    </div>
                    <div>
                        <label class="relative flex">
                            <input class="sc-input h-12 rounded-xl pl-4" id="password" name="password" type="password" placeholder="{{ __('messages.auth.password_placeholder') }}" required autocomplete="current-password">
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary">
                            {{ __('messages.common.remember_me') }}
                        </label>
                        <a href="{{ route('register') }}" class="text-xs font-semibold text-primary hover:text-primary-focus">{{ __('messages.auth.stagiaire_registration') }}</a>
                    </div>
                    <button class="sc-btn sc-btn-primary h-11 w-full rounded-xl shadow-lg shadow-primary/25">{{ __('messages.auth.sign_in') }}</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
