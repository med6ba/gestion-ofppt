<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#009245">
    <title>{{ __('messages.common.register') }} - {{ __('messages.brand') }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="{{ asset('logo/ofppt-logo.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @php
        $oldEmailLocalPart = str(old('email_local_part', old('email', '')))->before('@')->toString();
    @endphp
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-10">
        <div class="w-full sc-card p-6">
            <div class="flex items-center justify-between gap-4">
                <a href="{{ route('landing') }}">
                    <img class="h-16 w-16 object-contain drop-shadow-[0_0_18px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                </a>
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <x-language-switcher />
                    <a href="{{ route('landing') }}" class="text-sm font-semibold text-slate-600 hover:text-primary">{{ __('messages.nav.home') }}</a>
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-campus-700">{{ __('messages.common.back_to_login') }}</a>
                </div>
            </div>
            <h1 class="mt-4 text-2xl font-bold">{{ __('messages.auth.register_title') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('messages.auth.register_subtitle') }}</p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4">
                @csrf
                <div>
                    <x-form.label>{{ __('messages.auth.full_name') }}</x-form.label>
                    <input class="sc-input mt-1" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <x-form.label>{{ __('messages.common.email') }}</x-form.label>
                    <label class="email-domain-field mt-1" dir="ltr">
                        <input class="email-domain-input" name="email_local_part" type="text" value="{{ $oldEmailLocalPart }}" placeholder="{{ __('messages.auth.email_placeholder') }}" required autocomplete="username" inputmode="email" dir="ltr">
                        <span class="email-domain-suffix" dir="ltr">@ofppt-edu.ma</span>
                    </label>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-form.label>{{ __('messages.auth.phone') }}</x-form.label>
                        <input class="sc-input mt-1" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <x-form.label>{{ __('messages.auth.registration_number') }}</x-form.label>
                        <input class="sc-input mt-1" name="registration_number" value="{{ old('registration_number') }}">
                    </div>
                </div>
                <div>
                    <x-form.label>{{ __('messages.common.cni') }}</x-form.label>
                    <input class="sc-input mt-1 uppercase" name="cni" value="{{ old('cni') }}" required>
                </div>
                <div>
                    <x-form.label>{{ __('messages.common.group') }}</x-form.label>
                    <select class="sc-input mt-1" name="group_id" required>
                        <option value="">{{ __('messages.auth.choose_group') }}</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->code }} - {{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <x-form.label>{{ __('messages.common.password') }}</x-form.label>
                        <input class="sc-input mt-1" type="password" name="password" required>
                    </div>
                    <div>
                        <x-form.label>{{ __('messages.common.confirm_password') }}</x-form.label>
                        <input class="sc-input mt-1" type="password" name="password_confirmation" required>
                    </div>
                </div>
                <button class="sc-btn sc-btn-primary">{{ __('messages.auth.create_pending_account') }}</button>
            </form>
        </div>
    </main>
</body>
</html>
