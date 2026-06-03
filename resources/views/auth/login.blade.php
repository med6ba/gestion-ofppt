<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#6366f1">
    <title>Login - Smart Campus OFPPT</title>
    <link rel="manifest" href="/manifest.json">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white">
    <main class="flex min-h-screen w-full flex-col lg:flex-row">
        <section class="relative hidden w-full overflow-hidden lg:flex lg:w-1/2 xl:w-[55%]">
            <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('images/campus/ofppt-lab-login.jpeg') }}" alt="OFPPT digital classroom">
            <div class="absolute inset-0 bg-slate-950/45"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/20 to-transparent"></div>

            <div class="relative z-10 flex h-full w-full flex-col justify-between px-12 py-10">
                <a href="{{ route('login') }}" class="flex items-center gap-3">
                    <img class="h-14 w-44 object-contain" src="{{ asset('images/ofppt-logo-white.svg') }}" alt="OFPPT logo">
                </a>

                <div class="max-w-xl pb-8">
                    <div class="mb-4 inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-white/90 backdrop-blur">
                        Smart Campus OFPPT
                    </div>
                    <h1 class="text-4xl font-bold leading-tight text-white">Gestion intelligente du campus OFPPT</h1>
                    <p class="mt-4 max-w-lg text-base leading-relaxed text-white/85">Pointage QR, emploi du temps, communication interne et suivi des stagiaires dans une interface propre et moderne.</p>
                </div>
            </div>
        </section>

        <section class="relative flex w-full min-h-screen flex-col items-center justify-center bg-white px-4 py-12 lg:min-h-0 lg:w-1/2 lg:px-8 xl:w-[45%]">
            <div class="fixed left-6 top-5 z-10 lg:hidden">
                <a href="{{ route('login') }}" class="flex items-center gap-2">
                    <img class="h-10 w-32 object-contain" src="{{ asset('images/ofppt-logo.svg') }}" alt="OFPPT logo">
                    <span class="font-bold text-slate-700">Smart Campus OFPPT</span>
                </a>
            </div>

            <div class="w-full max-w-sm">
                <div class="mb-8 text-center lg:text-left">
                    <h2 class="text-2xl font-bold text-slate-700 sm:text-3xl">Welcome Back</h2>
                    <p class="mt-2 text-sm text-slate-400">Please sign in to continue</p>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('passkey.start') }}" class="rounded-2xl border border-primary/15 bg-primary/5 p-4">
                    @csrf
                    <div class="mb-3">
                        <div class="text-sm font-bold text-slate-700">Passkey priority access</div>
                        <div class="mt-1 text-xs text-slate-500">Fingerprint, Face ID, Windows Hello, or device PIN</div>
                    </div>
                    <button class="sc-btn sc-btn-primary h-11 w-full rounded-xl shadow-lg shadow-primary/25" type="submit">Continue with Passkey</button>
                </form>

                <div class="my-5 flex items-center gap-3 text-xs font-semibold uppercase text-slate-400">
                    <span class="h-px flex-1 bg-slate-200"></span>
                    <span>Email/password fallback</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>

                <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="relative flex">
                            <input class="sc-input h-12 rounded-xl pl-4" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Email address" required autofocus autocomplete="email">
                        </label>
                    </div>
                    <div>
                        <label class="relative flex">
                            <input class="sc-input h-12 rounded-xl pl-4" id="password" name="password" type="password" placeholder="Password" required autocomplete="current-password">
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="inline-flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary">
                            Remember me
                        </label>
                        <a href="{{ route('register') }}" class="text-xs font-semibold text-primary hover:text-primary-focus">Stagiaire registration</a>
                    </div>
                    <button class="sc-btn sc-btn-primary h-11 w-full rounded-xl shadow-lg shadow-primary/25">Sign in</button>
                </form>
            </div>
        </section>
    </main>
</body>
</html>
