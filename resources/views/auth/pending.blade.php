<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('messages.auth.pending_title') }} - {{ __('messages.brand') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <main class="flex min-h-screen items-center justify-center px-4">
        <div class="fixed right-6 top-5 z-10">
            <x-language-switcher />
        </div>
        <div class="max-w-md sc-card p-6 text-center">
            <img class="mx-auto h-20 w-20 object-contain drop-shadow-[0_0_18px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
            <div class="mt-3 text-xl font-bold text-campus-700">{{ __('messages.brand') }}</div>
            <h1 class="mt-6 text-2xl font-bold">{{ __('messages.auth.pending_title') }}</h1>
            <p class="mt-3 text-sm text-slate-500">{{ __('messages.auth.pending_message') }}</p>
            <a href="{{ route('login') }}" class="sc-btn sc-btn-primary mt-6">{{ __('messages.common.back_to_login') }}</a>
        </div>
    </main>
</body>
</html>
