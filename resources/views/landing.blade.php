<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#005b9f">
    <title>Smart Campus OFPPT</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="{{ asset('logo/ofppt-logo.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <header class="fixed inset-x-0 top-0 z-30 border-b border-white/10 bg-slate-950/40 backdrop-blur">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3">
            <a href="{{ route('landing') }}" class="flex items-center gap-3">
                <img class="h-12 w-12 object-contain" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                <span class="text-sm font-black uppercase tracking-normal text-white">{{ __('messages.brand') }}</span>
            </a>
            <nav class="flex items-center gap-2">
                <x-language-switcher dark />
                <a href="{{ route('login') }}" class="sc-btn border border-white/25 bg-white/10 text-white hover:bg-white/20">{{ __('messages.common.login') }}</a>
                <a href="{{ route('register') }}" class="sc-btn bg-white text-primary hover:bg-slate-100">{{ __('messages.common.register') }}</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="relative min-h-[88vh] overflow-hidden">
            <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('images/campus/ofppt-lab-login.jpeg') }}" alt="OFPPT digital classroom">
            <div class="absolute inset-0 bg-slate-950/60"></div>
            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-white to-transparent"></div>

            <div class="relative z-10 mx-auto flex min-h-[88vh] max-w-7xl items-center px-4 pb-16 pt-28">
                <div class="max-w-3xl">
                    <div class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold uppercase text-white/90 backdrop-blur">{{ __('messages.landing.badge') }}</div>
                    <h1 class="mt-5 text-4xl font-black leading-tight text-white sm:text-6xl">{{ __('messages.landing.title') }}</h1>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">
                        {{ __('messages.landing.text') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="sc-btn sc-btn-primary px-5 py-3">{{ __('messages.common.login') }}</a>
                        <a href="{{ route('register') }}" class="sc-btn border border-white/30 bg-white/10 px-5 py-3 text-white hover:bg-white/20">{{ __('messages.landing.register_stagiaire') }}</a>
                    </div>
                </div>
            </div>
        </section>

        <section class="mx-auto -mt-10 grid max-w-7xl gap-4 px-4 pb-16 sm:grid-cols-2 lg:grid-cols-4">
            <article class="sc-card p-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-ui.icon name="qr" /></div>
                <h2 class="mt-4 font-black">{{ __('messages.landing.features.qr_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('messages.landing.features.qr_text') }}</p>
            </article>
            <article class="sc-card p-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-campus-50 text-campus-700"><x-ui.icon name="calendar" /></div>
                <h2 class="mt-4 font-black">{{ __('messages.landing.features.absence_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('messages.landing.features.absence_text') }}</p>
            </article>
            <article class="sc-card p-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-sky-50 text-sky-700"><x-ui.icon name="book" /></div>
                <h2 class="mt-4 font-black">{{ __('messages.landing.features.attestation_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('messages.landing.features.attestation_text') }}</p>
            </article>
            <article class="sc-card p-5">
                <div class="flex size-10 items-center justify-center rounded-lg bg-amber-50 text-amber-700"><x-ui.icon name="dashboard" /></div>
                <h2 class="mt-4 font-black">{{ __('messages.landing.features.dashboard_title') }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-500">{{ __('messages.landing.features.dashboard_text') }}</p>
            </article>
        </section>
    </main>
</body>
</html>
