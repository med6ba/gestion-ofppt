@props(['title' => 'Smart Campus OFPPT', 'collapseSidebar' => false])

@php
    $user = auth()->user();
    $role = $user?->role;
    $navByRole = [
        'directeur' => [
            ['label' => 'Accueil', 'icon' => 'dashboard', 'route' => $user?->dashboardRoute(), 'active' => ['dashboard.redirect', 'directeur.dashboard']],
            ['label' => 'Gestion Utilisateurs', 'icon' => 'users', 'children' => [
                ['label' => 'Tous les utilisateurs', 'icon' => 'users', 'route' => 'users.index', 'active' => ['users.*', 'profile.show']],
                ['label' => 'Créer un utilisateur', 'icon' => 'user-plus', 'route' => 'users.index', 'params' => ['panel' => 'create'], 'active' => ['users.*']],
                ['label' => 'Stagiaires en attente', 'icon' => 'user-clock', 'route' => 'users.index', 'params' => ['role' => 'stagiaire', 'status' => 'pending'], 'active' => ['users.*', 'stagiaires.*']],
            ]],
            ['label' => 'Enseignement', 'icon' => 'academic', 'children' => [
                ['label' => 'Emplois du temps', 'icon' => 'calendar', 'route' => 'timetable.index', 'active' => ['timetable.*']],
                ['label' => 'Groupes', 'icon' => 'users-group', 'route' => 'resources.index', 'active' => ['resources.*']],
                ['label' => 'Filières', 'icon' => 'book', 'route' => 'resources.index', 'active' => ['resources.*']],
                ['label' => 'Modules', 'icon' => 'layers', 'route' => 'resources.index', 'active' => ['resources.*']],
                ['label' => 'Salles', 'icon' => 'building', 'route' => 'resources.index', 'active' => ['resources.*']],
            ]],
            ['label' => 'Absences', 'icon' => 'clock', 'children' => [
                ['label' => 'Tableau global', 'icon' => 'chart-pie', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
                ['label' => 'Retards / Absences', 'icon' => 'user-minus', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
                ['label' => 'Étudiants à risque', 'icon' => 'alert-triangle', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
            ]],
            ['label' => 'Communication', 'icon' => 'messages', 'children' => [
                ['label' => 'Annoncements', 'icon' => 'megaphone', 'route' => 'announcements.index', 'active' => ['announcements.*']],
                ['label' => 'Chat', 'icon' => 'chat-bubble', 'route' => 'chat.index', 'active' => ['chat.*']],
            ]],
            ['label' => 'Rapports / Statistiques', 'icon' => 'chart', 'children' => [
                ['label' => 'Statistiques', 'icon' => 'chart-bar', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
                ['label' => 'Leaderboard Présence XP', 'icon' => 'award', 'route' => 'attendance.leaderboard', 'active' => ['attendance.leaderboard']],
            ]],
            ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'settings.index', 'active' => ['settings.*']],
        ],
        'surveillant' => [
            ['label' => 'Accueil', 'icon' => 'dashboard', 'route' => $user?->dashboardRoute(), 'active' => ['dashboard.redirect', 'surveillant.dashboard']],
            ['label' => 'Gestion Utilisateurs', 'icon' => 'users', 'children' => [
                ['label' => 'Stagiaires', 'icon' => 'users', 'route' => 'users.index', 'params' => ['role' => 'stagiaire'], 'active' => ['users.*', 'profile.show']],
                ['label' => 'Approbations', 'icon' => 'user-clock', 'route' => 'users.index', 'params' => ['role' => 'stagiaire', 'status' => 'pending'], 'active' => ['users.*', 'stagiaires.*']],
            ]],
            ['label' => 'Enseignement', 'icon' => 'academic', 'children' => [
                ['label' => 'Emplois du temps', 'icon' => 'calendar', 'route' => 'timetable.index', 'active' => ['timetable.*']],
                ['label' => 'Groupes', 'icon' => 'users-group', 'route' => 'resources.index', 'active' => ['resources.*']],
                ['label' => 'Salles', 'icon' => 'building', 'route' => 'resources.index', 'active' => ['resources.*']],
            ]],
            ['label' => 'Absences', 'icon' => 'clock', 'children' => [
                ['label' => 'Suivi des absences', 'icon' => 'chart-pie', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
                ['label' => 'Retards importants', 'icon' => 'user-minus', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
                ['label' => 'Étudiants à risque', 'icon' => 'alert-triangle', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
            ]],
            ['label' => 'Communication', 'icon' => 'messages', 'children' => [
                ['label' => 'Annoncements', 'icon' => 'megaphone', 'route' => 'announcements.index', 'active' => ['announcements.*']],
                ['label' => 'Chat', 'icon' => 'chat-bubble', 'route' => 'chat.index', 'active' => ['chat.*']],
            ]],
            ['label' => 'Rapports / Statistiques', 'icon' => 'chart', 'route' => 'attendance.reports', 'active' => ['attendance.reports']],
            ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'settings.index', 'active' => ['settings.*']],
        ],
        'formateur' => [
            ['label' => 'Accueil', 'icon' => 'dashboard', 'route' => $user?->dashboardRoute(), 'active' => ['dashboard.redirect', 'formateur.dashboard']],
            ['label' => 'Enseignement', 'icon' => 'academic', 'children' => [
                ['label' => 'Mon Emploi du Temps', 'icon' => 'calendar', 'route' => 'timetable.mine', 'active' => ['timetable.mine']],
                ['label' => 'Mes Classes / Groupes', 'icon' => 'users-group', 'route' => 'formateur.dashboard', 'active' => ['formateur.dashboard']],
                ['label' => 'Mes Matières / Modules', 'icon' => 'layers', 'route' => 'formateur.dashboard', 'active' => ['formateur.dashboard']],
                ['label' => 'Mes Stagiaires', 'icon' => 'users', 'route' => 'formateur.dashboard', 'active' => ['formateur.dashboard']],
            ]],
            ['label' => 'Absences', 'icon' => 'clock', 'children' => [
                ['label' => 'Mes séances', 'icon' => 'calendar', 'route' => 'attendance.index', 'active' => ['attendance.index']],
                ['label' => 'Pointage', 'icon' => 'check-circle', 'route' => 'attendance.index', 'active' => ['attendance.show', 'attendance.qr.*', 'attendance.manual.*']],
                ['label' => 'Retards à valider', 'icon' => 'clock', 'route' => 'attendance.index', 'active' => ['attendance.late.*']],
            ]],
            ['label' => 'Communication', 'icon' => 'messages', 'children' => [
                ['label' => 'Annoncements', 'icon' => 'megaphone', 'route' => 'announcements.index', 'active' => ['announcements.*']],
                ['label' => 'Chat', 'icon' => 'chat-bubble', 'route' => 'chat.index', 'active' => ['chat.*']],
            ]],
            ['label' => 'Rapports / Statistiques', 'icon' => 'chart', 'children' => [
                ['label' => 'Suivi présence', 'icon' => 'chart-bar', 'route' => 'attendance.leaderboard', 'active' => ['attendance.leaderboard']],
                ['label' => 'Leaderboard groupe', 'icon' => 'award', 'route' => 'attendance.leaderboard', 'active' => ['attendance.leaderboard']],
            ]],
        ],
        'stagiaire' => [
            ['label' => 'Accueil', 'icon' => 'dashboard', 'route' => $user?->dashboardRoute(), 'active' => ['dashboard.redirect', 'stagiaire.dashboard']],
            ['label' => 'Mon emploi du temps', 'icon' => 'calendar', 'route' => 'timetable.mine', 'active' => ['timetable.mine']],
            ['label' => 'Mes modules', 'icon' => 'layers', 'route' => 'timetable.mine', 'active' => ['timetable.mine']],
            ['label' => 'Mes absences', 'icon' => 'clock', 'route' => 'stagiaire.dashboard', 'active' => ['attendance.check-in', 'attendance.scan', 'attendance.code.*', 'stagiaire.dashboard']],
            ['label' => 'Communication', 'icon' => 'messages', 'children' => [
                ['label' => 'Annoncements', 'icon' => 'megaphone', 'route' => 'announcements.index', 'active' => ['announcements.*']],
                ['label' => 'Chat', 'icon' => 'chat-bubble', 'route' => 'chat.index', 'active' => ['chat.*']],
            ]],
            ['label' => 'Mon score / XP', 'icon' => 'award', 'route' => 'attendance.leaderboard', 'active' => ['attendance.leaderboard']],
            ['label' => 'Paramètres', 'icon' => 'settings', 'route' => 'settings.index', 'active' => ['settings.*']],
        ],
    ];
    $navGroups = collect($navByRole[$role] ?? []);
    $isItemActive = function (array $item): bool {
        $patterns = array_filter($item['active'] ?? [$item['route'] ?? null]);

        if (!$patterns || !request()->routeIs(...$patterns)) {
            return false;
        }

        foreach (($item['params'] ?? []) as $key => $value) {
            if ((string) request($key) !== (string) $value) {
                return false;
            }
        }

        return true;
    };
    $activeGroups = $navGroups
        ->filter(fn ($group) => collect($group['children'] ?? [])->contains(fn ($item) => $isItemActive($item)))
        ->pluck('label')
        ->values();
    $flatNav = $navGroups->flatMap(fn ($item) => $item['children'] ?? [$item]);
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
    <div class="manar-shell" x-data="{ sidebarOpen: false, sidebarCollapsed: @js($collapseSidebar), openGroups: @js($activeGroups) }" :class="{ 'is-sidebar-open': sidebarOpen, 'is-sidebar-collapsed': sidebarCollapsed }">
        <aside class="manar-sidebar" :class="{ 'mobile-open': sidebarOpen, 'collapsed': sidebarCollapsed }">
            <div class="manar-sidebar-logo">
                <a href="{{ route('dashboard.redirect') }}" class="flex min-w-0 items-center gap-3" wire:navigate>
                    <img class="h-10 w-14 rounded-xl bg-white object-contain p-1.5 shadow-sm ring-1 ring-slate-100" src="{{ asset('images/ofppt-mark.svg') }}" alt="OFPPT logo">
                    <div class="min-w-0 menu-text">
                        <div class="truncate text-sm font-bold text-slate-800">Smart Campus</div>
                        <div class="truncate text-xs font-semibold text-primary">OFPPT</div>
                    </div>
                </a>
            </div>

            <div class="shrink-0 mt-4 mb-2 flex items-center rounded-xl bg-white shadow-sm ring-1 ring-slate-100 transition-all duration-300" :class="sidebarCollapsed ? 'mx-2 p-1 gap-0 justify-center' : 'mx-4 p-2.5 gap-3'">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-lg font-bold text-white">{{ strtoupper(substr($user?->name ?? 'S', 0, 1)) }}</div>
                <div class="min-w-0 menu-text">
                    <div class="truncate text-sm font-bold text-slate-800">{{ $user?->name }}</div>
                    <div class="truncate text-[11px] text-slate-400">{{ $user?->roleLabel() }}</div>
                </div>
            </div>

            <nav class="manar-menu">
                @foreach ($navGroups as $group)
                    @php
                        $children = collect($group['children'] ?? []);
                        $groupActive = $children->isNotEmpty()
                            ? $children->contains(fn ($item) => $isItemActive($item))
                            : $isItemActive($group);
                    @endphp

                    @if ($children->isEmpty())
                        <a href="{{ route($group['route'], $group['params'] ?? []) }}" class="menu-item {{ $groupActive ? 'active' : '' }}" title="{{ $group['label'] }}" @click="sidebarOpen = false" wire:navigate>
                            <span class="menu-icon"><x-ui.icon :name="$group['icon']" /></span>
                            <span class="menu-text">{{ $group['label'] }}</span>
                        </a>
                    @else
                        <div class="menu-group" x-data="{ label: @js($group['label']) }">
                            <button type="button" class="menu-item w-[calc(100%-0.5rem)] {{ $groupActive ? 'active' : '' }}" title="{{ $group['label'] }}" @click="sidebarCollapsed ? sidebarCollapsed = false : (openGroups.includes(label) ? openGroups = openGroups.filter(item => item !== label) : openGroups.push(label))">
                                <span class="menu-icon"><x-ui.icon :name="$group['icon']" /></span>
                                <span class="menu-text flex-1 text-left">{{ $group['label'] }}</span>
                                <svg class="menu-text size-4 transition" :class="openGroups.includes(label) && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                </svg>
                            </button>

                            <div class="menu-text ml-4 mt-1 space-y-1 border-l border-slate-200 pl-3" x-show="openGroups.includes(label)">
                                @foreach ($children as $item)
                                    @php $itemActive = $isItemActive($item); @endphp
                                    <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="submenu-item {{ $itemActive ? 'active' : '' }}" @click="sidebarOpen = false" wire:navigate>
                                        <x-ui.icon :name="$item['icon']" size="size-4" class="mr-2 text-slate-400 shrink-0" />
                                        <span>{{ $item['label'] }}</span>
                                        @if (($item['badge'] ?? null) === 'notifications' && $unreadCount)
                                            <span class="menu-badge">{{ $unreadCount }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="manar-sidebar-footer shrink-0">
                <button class="logout-button sc-btn sc-btn-secondary w-full mb-3 hidden lg:flex items-center" style="gap: 0.5rem;" @click="sidebarCollapsed = !sidebarCollapsed" type="button" aria-label="Réduire le menu">
                    <svg class="size-4 shrink-0 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                    <span class="menu-text">Réduire le menu</span>
                </button>
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
            <header class="manar-header flex items-center justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="menu-toggle lg:hidden" @click="sidebarOpen = true" type="button" aria-label="Open menu">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase text-slate-400">{{ $user?->roleLabel() }}</div>
                        <h1 class="truncate text-xl font-bold text-slate-800">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-3">
                    <!-- Search bar -->
                    <div class="relative hidden sm:block mr-2">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                        </div>
                        <input type="text" class="block w-full rounded-full border-0 bg-slate-100 py-2 pl-10 pr-4 text-sm text-slate-900 placeholder:text-slate-400 focus:bg-white focus:ring-2 focus:ring-primary sm:w-64 transition-all" placeholder="Search here...">
                    </div>

                    <!-- Icons -->
                    <button class="manar-icon-btn hidden sm:flex text-amber-500 hover:text-amber-600 hover:bg-amber-50" aria-label="Theme">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                    </button>
                    
                    <button class="manar-icon-btn hidden sm:flex text-lg" aria-label="Language">🇫🇷</button>
                    
                    <a href="{{ route('notifications.index') }}" class="manar-icon-btn relative" aria-label="Notifications" wire:navigate>
                        <span class="bell-dot"></span>
                        <x-ui.icon name="bell" />
                        @if ($unreadCount ?? 0)
                            <span class="absolute -right-1 -top-1 rounded-full bg-secondary px-1.5 py-0.5 text-[10px] font-bold text-white">{{ $unreadCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('chat.index') }}" class="manar-icon-btn hidden sm:flex" aria-label="Chat" wire:navigate>
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="manar-icon-btn" title="Logout">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                        </button>
                    </form>

                    <button class="manar-icon-btn hidden sm:flex" aria-label="Settings">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                    </button>
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

    <nav class="mobile-nav lg:hidden">
        @php
            $mobile = $flatNav
                ->filter(fn ($item) => in_array($item['label'], ['Accueil', 'Tous les utilisateurs', 'Emplois du temps', 'Mon Emploi du Temps', 'Mes séances', 'Mes absences', 'Chat', 'Mon score / XP', 'Statistiques'], true))
                ->take(4);
        @endphp
        @foreach ($mobile as $item)
            @php $isActive = $isItemActive($item); @endphp
            <a href="{{ route($item['route'], $item['params'] ?? []) }}" class="mobile-nav-item {{ $isActive ? 'active' : '' }}">
                <span class="text-base">{{ $item['icon'] ?? '•' }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    @stack('scripts')
</body>
</html>
