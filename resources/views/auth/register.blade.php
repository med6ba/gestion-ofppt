<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Smart Campus OFPPT</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50">
    <main class="mx-auto flex min-h-screen max-w-xl items-center px-4 py-10">
        <div class="w-full sc-card p-6">
            <div class="flex items-center justify-between gap-4">
                <img class="h-16 w-16 object-contain drop-shadow-[0_0_18px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
                <a href="{{ route('login') }}" class="text-sm font-semibold text-campus-700">Back to login</a>
            </div>
            <h1 class="mt-4 text-2xl font-bold">Stagiaire registration</h1>
            <p class="mt-2 text-sm text-slate-500">Your account will stay pending until approved by administration.</p>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-4">
                @csrf
                <div>
                    <label class="sc-label">Full name</label>
                    <input class="sc-input mt-1" name="name" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="sc-label">Email</label>
                    <input class="sc-input mt-1" type="email" name="email" value="{{ old('email') }}" required>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="sc-label">Phone</label>
                        <input class="sc-input mt-1" name="phone" value="{{ old('phone') }}">
                    </div>
                    <div>
                        <label class="sc-label">Registration number</label>
                        <input class="sc-input mt-1" name="registration_number" value="{{ old('registration_number') }}">
                    </div>
                </div>
                <div>
                    <label class="sc-label">Group</label>
                    <select class="sc-input mt-1" name="group_id" required>
                        <option value="">Choose group</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected(old('group_id') == $group->id)>{{ $group->code }} - {{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="sc-label">Password</label>
                        <input class="sc-input mt-1" type="password" name="password" required>
                    </div>
                    <div>
                        <label class="sc-label">Confirm password</label>
                        <input class="sc-input mt-1" type="password" name="password_confirmation" required>
                    </div>
                </div>
                <button class="sc-btn sc-btn-primary">Create pending account</button>
            </form>
        </div>
    </main>
</body>
</html>
