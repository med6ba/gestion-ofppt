@props(['title' => 'Smart Campus OFPPT'])

@php
    $user = auth()->user();
    $role = $user?->role;
    $nav = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'section' => 'Campus', 'route' => $user?->dashboardRoute(), 'active' => ['dashboard.redirect', $user?->dashboardRoute()], 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
        ['label' => 'Users', 'icon' => 'users', 'section' => 'Administration', 'route' => 'users.index', 'active' => ['users.*', 'staff.*', 'stagiaires.*', 'profile.show'], 'roles' => ['directeur', 'surveillant']],
        ['label' => 'Timetable', 'icon' => 'calendar', 'section' => 'Administration', 'route' => 'timetable.index', 'active' => ['timetable.index', 'timetable.active-week', 'timetable.store', 'timetable.edit', 'timetable.update', 'timetable.destroy'], 'roles' => ['surveillant']],
        ['label' => 'Resources', 'icon' => 'layers', 'section' => 'Administration', 'route' => 'resources.index', 'active' => ['resources.*'], 'roles' => ['surveillant']],
        ['label' => 'My Schedule', 'icon' => 'clock', 'section' => 'Daily work', 'route' => 'timetable.mine', 'active' => ['timetable.mine'], 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
        ['label' => 'Attendance', 'icon' => 'check', 'section' => 'Daily work', 'route' => 'attendance.index', 'active' => ['attendance.index', 'attendance.show', 'attendance.manual.*', 'attendance.qr.*'], 'roles' => ['formateur']],
        ['label' => 'Check In', 'icon' => 'qr', 'section' => 'Daily work', 'route' => 'attendance.check-in', 'active' => ['attendance.check-in', 'attendance.scan', 'attendance.code.*'], 'roles' => ['stagiaire']],
        ['label' => 'Reports', 'icon' => 'chart', 'section' => 'Follow-up', 'route' => 'attendance.reports', 'active' => ['attendance.reports'], 'roles' => ['directeur', 'surveillant']],
        ['label' => 'Chat', 'icon' => 'messages', 'section' => 'Communication', 'route' => 'chat.index', 'active' => ['chat.*'], 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
        ['label' => 'Notifications', 'icon' => 'bell', 'section' => 'Communication', 'route' => 'notifications.index', 'active' => ['notifications.*'], 'badge' => 'notifications', 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
        ['label' => 'CampusAI', 'icon' => 'ai', 'section' => 'Communication', 'route' => 'ai.index', 'active' => ['ai.*'], 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
    ];
    $visibleNav = collect($nav)->filter(fn ($item) => $role && in_array($role, $item['roles'], true));
    $sections = $visibleNav->groupBy('section');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#116d5b">
    <title>{{ $title ?? 'Smart Campus OFPPT' }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="{{ asset('images/ofppt-mark.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-700 antialiased">
    <div class="manar-shell" x-data="{ sidebarOpen: false, sidebarCollapsed: false }" :class="{ 'is-sidebar-open': sidebarOpen, 'is-sidebar-collapsed': sidebarCollapsed }">
        <aside class="manar-sidebar" :class="{ 'mobile-open': sidebarOpen, 'collapsed': sidebarCollapsed }">
            <div class="manar-sidebar-logo">
                <a href="{{ route('dashboard.redirect') }}" class="flex min-w-0 items-center gap-3">
                    <img class="h-10 w-14 rounded-xl bg-white object-contain p-1.5 shadow-sm ring-1 ring-slate-100" src="{{ asset('images/ofppt-mark.svg') }}" alt="OFPPT logo">
                    <div class="min-w-0 menu-text">
                        <div class="truncate text-sm font-bold text-slate-800">Smart Campus</div>
                        <div class="truncate text-xs font-semibold text-primary">OFPPT</div>
                    </div>
                </a>
            </div>

            <nav class="manar-menu">
                @foreach ($sections as $section => $items)
                    <div class="section-title menu-text">{{ $section }}</div>
                    @foreach ($items as $item)
                        @php
                            $isActive = request()->routeIs(...array_filter($item['active'] ?? [$item['route']]));
                        @endphp
                        <a href="{{ route($item['route']) }}" class="menu-item {{ $isActive ? 'active' : '' }}" title="{{ $item['label'] }}" @click="sidebarOpen = false">
                            <span class="menu-icon">
                                <x-ui.icon :name="$item['icon']" />
                            </span>
                            <span class="menu-text">{{ $item['label'] }}</span>
                            @if (($item['badge'] ?? null) === 'notifications' && $unreadCount)
                                <span class="menu-badge menu-text">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    @endforeach
                @endforeach
            </nav>

            <div class="manar-sidebar-footer">
                <div class="manar-user-mini">
                    <div class="avatar-initial">{{ strtoupper(substr($user?->name ?? 'S', 0, 1)) }}</div>
                    <div class="min-w-0 menu-text">
                        <div class="truncate text-xs font-bold text-slate-700">{{ $user?->name }}</div>
                        <div class="truncate text-[11px] text-slate-400">{{ $user?->roleLabel() }}</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="mt-3">
                    @csrf
                    <button class="logout-button sc-btn sc-btn-secondary w-full" title="Logout">
                        <x-ui.icon name="logout" size="size-4" />
                        <span class="menu-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="manar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <div class="manar-main">
            <header class="manar-header">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="menu-toggle md:hidden" @click="sidebarOpen = true" type="button" aria-label="Open menu">
                        <span></span><span></span><span></span>
                    </button>
                    <button class="menu-toggle hidden md:flex" :class="sidebarCollapsed && 'active'" @click="sidebarCollapsed = !sidebarCollapsed" type="button" aria-label="Collapse menu">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <img class="hidden h-5 w-9 object-contain sm:block" src="{{ asset('images/ofppt-mark.svg') }}" alt="OFPPT">
                            <div class="text-[11px] font-bold uppercase text-slate-400">{{ $user?->roleLabel() }}</div>
                        </div>
                        <h1 class="truncate text-xl font-bold text-slate-800">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <div class="manar-search hidden sm:flex">
                        <span class="text-slate-400">Search</span>
                    </div>
                    <a href="{{ route('notifications.index') }}" class="manar-icon-btn relative" aria-label="Notifications">
                        <span class="bell-dot"></span>
                        <x-ui.icon name="bell" />
                        @if ($unreadCount)
                            <span class="absolute -right-1 -top-1 rounded-full bg-secondary px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadCount }}</span>
                        @endif
                    </a>
                    <div class="hidden items-center gap-2 rounded-full bg-white px-2 py-1.5 shadow-sm ring-1 ring-slate-100 sm:flex">
                        <div class="avatar-initial size-8 text-xs">{{ strtoupper(substr($user?->name ?? 'S', 0, 1)) }}</div>
                        <div class="max-w-32 truncate text-xs font-bold text-slate-700">{{ $user?->name }}</div>
                    </div>
                </div>
            </header>

            <main class="manar-content scrollbar-sm">
                @if (session('status'))
                    <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                        <ul class="space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <nav class="mobile-nav md:hidden">
        @php
            $mobile = $visibleNav
                ->filter(fn ($item) => in_array($item['label'], ['Dashboard', 'Users', 'Reports', 'My Schedule', 'Attendance', 'Check In', 'Chat', 'CampusAI'], true))
                ->take(4);
        @endphp
        @foreach ($mobile as $item)
            @php
                $isActive = request()->routeIs(...array_filter($item['active'] ?? [$item['route']]));
            @endphp
            <a href="{{ route($item['route']) }}" class="mobile-nav-item {{ $isActive ? 'active' : '' }}">
                <x-ui.icon :name="$item['icon']" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @stack('scripts')
</body>
</html>
