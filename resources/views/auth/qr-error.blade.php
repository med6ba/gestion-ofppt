<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'QR login' }} - Smart Campus OFPPT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <main class="mx-auto flex min-h-screen max-w-md items-center px-4 py-10">
        <section class="sc-card w-full p-6 text-center">
            <img class="mx-auto h-16 w-16 object-contain" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
            <div class="mx-auto mt-5 flex size-12 items-center justify-center rounded-full bg-rose-50 text-rose-700">
                <x-ui.icon name="alert-triangle" />
            </div>
            <h1 class="mt-4 text-2xl font-black text-slate-800">{{ $title ?? 'QR login indisponible' }}</h1>
            <p class="mt-3 text-sm leading-6 text-slate-500">{{ $message ?? 'Ce QR code ne peut pas etre utilise pour se connecter.' }}</p>
            <div class="mt-6 flex justify-center gap-3">
                <a href="{{ route('login') }}" class="sc-btn sc-btn-primary">{{ __('messages.common.login') }}</a>
                <a href="{{ route('landing') }}" class="sc-btn sc-btn-secondary">{{ __('messages.nav.home') }}</a>
            </div>
        </section>
    </main>
</body>
</html>
