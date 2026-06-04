<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#009245">
    <title>{{ __('messages.landing.title') }}</title>
    <meta name="description" content="{{ __('messages.landing.text') }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="{{ asset('logo/ofppt-logo.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-slate-800 antialiased {{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @php
        $stats = [
            ['value' => '418 000+', 'label' => __('messages.landing.stats.seats'), 'tone' => 'border-emerald-200 bg-emerald-50 text-emerald-800'],
            ['value' => '10 000+', 'label' => __('messages.landing.stats.staff'), 'tone' => 'border-sky-200 bg-sky-50 text-sky-800'],
            ['value' => '90%', 'label' => __('messages.landing.stats.offer'), 'tone' => 'border-amber-200 bg-amber-50 text-amber-800'],
            ['value' => '460+', 'label' => __('messages.landing.stats.trades'), 'tone' => 'border-rose-200 bg-rose-50 text-rose-800'],
            ['value' => '500+', 'label' => __('messages.landing.stats.institutes'), 'tone' => 'border-indigo-200 bg-indigo-50 text-indigo-800'],
        ];

        $features = [
            ['icon' => 'qr', 'title' => __('messages.landing.features.qr_title'), 'text' => __('messages.landing.features.qr_text'), 'tone' => 'bg-emerald-100 text-emerald-700'],
            ['icon' => 'check-circle', 'title' => __('messages.landing.features.attendance_title'), 'text' => __('messages.landing.features.attendance_text'), 'tone' => 'bg-sky-100 text-sky-700'],
            ['icon' => 'megaphone', 'title' => __('messages.landing.features.announcements_title'), 'text' => __('messages.landing.features.announcements_text'), 'tone' => 'bg-amber-100 text-amber-700'],
            ['icon' => 'calendar', 'title' => __('messages.landing.features.timetable_title'), 'text' => __('messages.landing.features.timetable_text'), 'tone' => 'bg-rose-100 text-rose-700'],
            ['icon' => 'profile', 'title' => __('messages.landing.features.absence_title'), 'text' => __('messages.landing.features.absence_text'), 'tone' => 'bg-indigo-100 text-indigo-700'],
            ['icon' => 'dashboard', 'title' => __('messages.landing.features.dashboard_title'), 'text' => __('messages.landing.features.dashboard_text'), 'tone' => 'bg-slate-100 text-slate-700'],
        ];

        $timeline = [
            ['step' => '01', 'text' => __('messages.landing.timeline.login')],
            ['step' => '02', 'text' => __('messages.landing.timeline.session')],
            ['step' => '03', 'text' => __('messages.landing.timeline.services')],
            ['step' => '04', 'text' => __('messages.landing.timeline.insight')],
        ];

        $officialLinks = [
            ['label' => __('messages.landing.key_figures'), 'url' => 'https://www.ofppt.ma/fr/chiffres-cles'],
            ['label' => __('messages.landing.find_training'), 'url' => 'https://www.ofppt.ma/fr'],
            ['label' => __('messages.landing.student_space'), 'url' => 'https://www.ofppt.ma/fr/formation-hybride'],
            ['label' => __('messages.landing.company_space'), 'url' => 'https://www.ofppt.ma/fr/services-aux-entreprises'],
        ];
    @endphp

    <header class="fixed inset-x-0 top-0 z-40 border-b border-white/15 bg-slate-950/55 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                <img class="h-12 w-12 shrink-0 object-contain" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                <span class="hidden text-sm font-black uppercase text-white sm:inline">{{ __('messages.brand') }}</span>
            </a>

            <nav class="hidden items-center gap-5 text-sm font-bold text-white/80 lg:flex">
                <a href="#ofppt" class="hover:text-white">{{ __('messages.landing.official_context') }}</a>
                <a href="#platform" class="hover:text-white">{{ __('messages.landing.platform') }}</a>
                <a href="#workflows" class="hover:text-white">{{ __('messages.landing.workflows') }}</a>
            </nav>

            <div class="flex items-center gap-2">
                <x-language-switcher dark />
                @auth
                    <a href="{{ route('dashboard.redirect') }}" class="sc-btn bg-white text-primary hover:bg-slate-100">{{ __('messages.landing.open_dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="sc-btn border border-white/25 bg-white/10 text-white hover:bg-white/20">{{ __('messages.common.login') }}</a>
                    <a href="{{ route('register') }}" class="hidden sc-btn bg-white text-primary hover:bg-slate-100 sm:inline-flex">{{ __('messages.common.register') }}</a>
                @endauth
            </div>
        </div>
    </header>

    <main>
        <section class="relative min-h-[88vh] overflow-hidden">
            <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('images/campus/ofppt-lab-login.jpeg') }}" alt="OFPPT digital classroom">
            <div class="absolute inset-0 bg-slate-950/65"></div>
            <div class="absolute inset-x-0 bottom-0 h-48 bg-gradient-to-t from-white to-transparent"></div>

            <div class="relative z-10 mx-auto flex min-h-[88vh] max-w-7xl items-center px-4 pb-28 pt-28">
                <div class="max-w-4xl">
                    <img class="mb-6 h-24 w-24 rounded-full bg-white object-contain p-2 shadow-2xl shadow-slate-950/40" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                    <div class="inline-flex rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-black uppercase text-white/90 backdrop-blur">{{ __('messages.landing.badge') }}</div>
                    <h1 class="mt-5 max-w-4xl text-4xl font-black leading-tight text-white sm:text-6xl lg:text-7xl">{{ __('messages.landing.title') }}</h1>
                    <p class="mt-5 max-w-3xl text-base leading-7 text-white/85 sm:text-xl">
                        {{ __('messages.landing.text') }}
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard.redirect') }}" class="sc-btn sc-btn-primary px-5 py-3">{{ __('messages.landing.open_dashboard') }}</a>
                        @else
                            <a href="{{ route('login') }}" class="sc-btn sc-btn-primary px-5 py-3">{{ __('messages.common.login') }}</a>
                            <a href="{{ route('register') }}" class="sc-btn border border-white/30 bg-white/10 px-5 py-3 text-white hover:bg-white/20">{{ __('messages.landing.register_stagiaire') }}</a>
                        @endauth
                        <a href="#platform" class="sc-btn border border-white/30 bg-white/10 px-5 py-3 text-white hover:bg-white/20">{{ __('messages.landing.explore') }}</a>
                    </div>
                </div>
            </div>
        </section>

        <section id="ofppt" class="relative z-20 mx-auto -mt-20 max-w-7xl px-4 pb-16">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach ($stats as $stat)
                    <article class="rounded-lg border p-5 shadow-sm {{ $stat['tone'] }}">
                        <div class="text-2xl font-black">{{ $stat['value'] }}</div>
                        <div class="mt-2 text-xs font-black uppercase tracking-normal opacity-80">{{ $stat['label'] }}</div>
                    </article>
                @endforeach
            </div>
            <p class="mt-3 text-xs font-semibold text-slate-500">{{ __('messages.landing.source_note') }}</p>
        </section>

        <section class="mx-auto grid max-w-7xl gap-10 px-4 pb-20 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
            <div>
                <div class="text-xs font-black uppercase text-primary">{{ __('messages.landing.official_context') }}</div>
                <h2 class="mt-3 text-3xl font-black text-slate-900 sm:text-4xl">{{ __('messages.landing.ofppt_heading') }}</h2>
                <p class="mt-4 text-base leading-8 text-slate-600">{{ __('messages.landing.ofppt_text') }}</p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($officialLinks as $link)
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="group rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-black text-slate-800">{{ $link['label'] }}</span>
                            <span class="grid size-8 place-items-center rounded-lg bg-primary/10 text-primary transition group-hover:bg-primary group-hover:text-white">&gt;</span>
                        </div>
                        <div class="mt-3 text-xs font-semibold text-slate-500">ofppt.ma</div>
                    </a>
                @endforeach
            </div>
        </section>

        <section id="platform" class="bg-slate-50 py-20">
            <div class="mx-auto max-w-7xl px-4">
                <div class="max-w-3xl">
                    <div class="text-xs font-black uppercase text-primary">{{ __('messages.landing.platform') }}</div>
                    <h2 class="mt-3 text-3xl font-black text-slate-900 sm:text-4xl">{{ __('messages.landing.platform_heading') }}</h2>
                    <p class="mt-4 text-base leading-8 text-slate-600">{{ __('messages.landing.platform_text') }}</p>
                </div>

                <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($features as $feature)
                        <article class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="flex size-11 items-center justify-center rounded-lg {{ $feature['tone'] }}">
                                <x-ui.icon :name="$feature['icon']" />
                            </div>
                            <h3 class="mt-4 text-lg font-black text-slate-900">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['text'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="workflows" class="mx-auto grid max-w-7xl gap-10 px-4 py-20 lg:grid-cols-[1fr_1fr] lg:items-start">
            <div>
                <div class="text-xs font-black uppercase text-primary">{{ __('messages.landing.workflows') }}</div>
                <h2 class="mt-3 text-3xl font-black text-slate-900 sm:text-4xl">{{ __('messages.landing.workflows_heading') }}</h2>
                <p class="mt-4 text-base leading-8 text-slate-600">{{ __('messages.landing.workflows_text') }}</p>
            </div>
            <div class="grid gap-3">
                @foreach ($timeline as $item)
                    <article class="flex gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="grid size-12 shrink-0 place-items-center rounded-lg bg-primary text-sm font-black text-white">{{ $item['step'] }}</div>
                        <p class="self-center text-sm font-bold leading-6 text-slate-700">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-slate-950 text-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 md:grid-cols-[1.2fr_0.8fr_0.8fr]">
            <div>
                <div class="flex items-center gap-3">
                    <img class="h-12 w-12 rounded-full bg-white object-contain p-1" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                    <div class="font-black">{{ __('messages.brand') }}</div>
                </div>
                <p class="mt-4 max-w-md text-sm leading-6 text-white/65">{{ __('messages.landing.footer_text') }}</p>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase text-white/80">{{ __('messages.landing.platform') }}</h3>
                <div class="mt-3 grid gap-2 text-sm text-white/65">
                    <a href="#ofppt" class="hover:text-white">{{ __('messages.landing.official_context') }}</a>
                    <a href="#platform" class="hover:text-white">{{ __('messages.landing.platform') }}</a>
                    <a href="#workflows" class="hover:text-white">{{ __('messages.landing.workflows') }}</a>
                </div>
            </div>
            <div>
                <h3 class="text-sm font-black uppercase text-white/80">{{ __('messages.landing.official_links') }}</h3>
                <div class="mt-3 grid gap-2 text-sm text-white/65">
                    @foreach ($officialLinks as $link)
                        <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ $link['label'] }}</a>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="border-t border-white/10 px-4 py-4 text-center text-xs font-semibold text-white/45">
            &copy; {{ now()->year }} Smart Campus OFPPT
        </div>
    </footer>
</body>
</html>
