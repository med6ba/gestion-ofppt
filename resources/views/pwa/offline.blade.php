<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline - Smart Campus OFPPT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50">
    <main class="flex min-h-screen items-center justify-center px-4">
        <section class="max-w-md sc-card p-6 text-center">
            <img class="mx-auto h-20 w-20 object-contain drop-shadow-[0_0_18px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
            <div class="mt-3 text-xl font-bold text-campus-700">Smart Campus OFPPT</div>
            <h1 class="mt-6 text-2xl font-bold">You are offline</h1>
            <p class="mt-3 text-sm text-slate-500">Previously opened pages may still be available. Reconnect to sync attendance, chat, and notifications.</p>
            <a href="/dashboard" class="sc-btn sc-btn-primary mt-6">Try dashboard</a>
        </section>
    </main>
</body>
</html>
