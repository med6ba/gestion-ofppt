@props(['title' => 'Smart Campus OFPPT', 'collapseSidebar' => false])

@php
    use App\Support\SmartCampusNavigation;

    $user = auth()->user();
    $navGroups = SmartCampusNavigation::groupsFor($user);
    $activeGroups = SmartCampusNavigation::activeGroups($navGroups, request(), $user);
    $isItemActive = fn (array $item): bool => SmartCampusNavigation::isActive($item, request(), $user);
    $hrefFor = fn (array $item): string => SmartCampusNavigation::hrefFor($item);
    $mobileNav = SmartCampusNavigation::mobileFor($user);
    $clockTimezone = 'Africa/Casablanca';
    $clockNow = now($clockTimezone);
    $clockOffsetMinutes = $clockNow->utcOffset();
    $clockOffsetHours = intdiv(abs($clockOffsetMinutes), 60);
    $clockOffsetRemainder = abs($clockOffsetMinutes) % 60;
    $clockOffset = 'UTC'.($clockOffsetMinutes >= 0 ? '+' : '-').$clockOffsetHours.($clockOffsetRemainder ? ':'.str_pad((string) $clockOffsetRemainder, 2, '0', STR_PAD_LEFT) : '');
@endphp
<!doctype html>
<html lang="{{ $currentLocale ?? app()->getLocale() }}" dir="{{ $textDirection ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#009245">
    <title>{{ $title ?? 'Smart Campus OFPPT' }}</title>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="{{ asset('logo/ofppt-logo.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-700 antialiased {{ ($textDirection ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr' }}">
    <div
        class="manar-shell"
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: localStorage.getItem('smartCampus.sidebarCollapsed') === 'true' || @js($collapseSidebar),
            openGroups: @js($activeGroups),
            logoutConfirmOpen: false,
        }"
        x-init="$watch('sidebarCollapsed', value => localStorage.setItem('smartCampus.sidebarCollapsed', value ? 'true' : 'false'))"
        @keydown.escape.window="sidebarOpen = false"
        :class="{ 'is-sidebar-open': sidebarOpen, 'is-sidebar-collapsed': sidebarCollapsed }"
    >
        <aside class="manar-sidebar" :class="{ 'mobile-open': sidebarOpen, 'collapsed': sidebarCollapsed }">
            <div class="manar-sidebar-logo">
                <a href="{{ route('dashboard.redirect') }}" class="flex min-w-0 items-center gap-3">
                    <img class="h-12 w-12 object-contain drop-shadow-[0_0_12px_rgba(59,130,246,0.35)]" src="{{ asset('logo/ofppt-logo.png') }}" alt="OFPPT logo">
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
                        <a href="{{ $hrefFor($group) }}" class="menu-item {{ $groupActive ? 'active' : '' }}" title="{{ $group['label'] }}" @click="sidebarOpen = false" @if($groupActive) aria-current="page" @endif>
                            <span class="menu-icon"><x-ui.icon :name="$group['icon']" /></span>
                            <span class="menu-text">{{ $group['label'] }}</span>
                        </a>
                    @else
                        <div class="menu-group" x-data="{ label: @js($group['label']) }">
                            <button type="button" class="menu-item w-[calc(100%-0.5rem)] {{ $groupActive ? 'active' : '' }}" title="{{ $group['label'] }}" :aria-expanded="openGroups.includes(label)" @click="sidebarCollapsed ? sidebarCollapsed = false : (openGroups.includes(label) ? openGroups = openGroups.filter(item => item !== label) : openGroups.push(label))">
                                <span class="menu-icon"><x-ui.icon :name="$group['icon']" /></span>
                                <span class="menu-text flex-1 text-start">{{ $group['label'] }}</span>
                                <svg class="menu-text size-4 transition" :class="openGroups.includes(label) && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                </svg>
                            </button>

                            <div class="menu-text ml-4 mt-1 space-y-1 border-l border-slate-200 pl-3" x-show="openGroups.includes(label)" x-collapse>
                                @foreach ($children as $item)
                                    @php $itemActive = $isItemActive($item); @endphp
                                    <a href="{{ $hrefFor($item) }}" class="submenu-item {{ $itemActive ? 'active' : '' }}" @click="sidebarOpen = false" @if($itemActive) aria-current="page" @endif>
                                        <x-ui.icon :name="$item['icon']" size="size-4" class="mr-2 text-slate-400 shrink-0" />
                                        <span>{{ $item['label'] }}</span>
                                        @if (($item['badge'] ?? null) === 'notifications')
                                            <span class="menu-badge" x-data="{ count: {{ $unreadCount ?? 0 }} }" @notification-received.window="count++" x-show="count > 0" x-text="count"></span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="manar-sidebar-footer shrink-0">
                <x-language-switcher class="mb-3 px-1 md:hidden" />
                <button class="logout-button sc-btn sc-btn-secondary w-full mb-3 hidden lg:flex items-center" style="gap: 0.5rem;" @click="sidebarCollapsed = !sidebarCollapsed" type="button" aria-label="{{ __('messages.nav.collapse_menu') }}">
                    <svg class="size-4 shrink-0 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                    <span class="menu-text">{{ __('messages.nav.collapse_menu') }}</span>
                </button>
                <button type="button" class="logout-button sc-btn sc-btn-secondary w-full" title="{{ __('messages.common.logout') }}" @click="logoutConfirmOpen = true">
                    <x-ui.icon name="logout" size="size-4" />
                    <span class="menu-text">{{ __('messages.common.logout') }}</span>
                </button>
            </div>
        </aside>

        <div class="manar-backdrop" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <div class="manar-main">
            <header class="manar-header flex items-center justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="menu-toggle lg:hidden" :class="{ 'active': sidebarOpen }" @click="sidebarOpen = !sidebarOpen" type="button" aria-label="{{ __('messages.nav.open_menu') }}">
                        <span></span><span></span><span></span>
                    </button>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase text-slate-400">{{ $user?->roleLabel() }}</div>
                        <h1 class="truncate text-xl font-bold text-slate-800">{{ $title ?? 'Dashboard' }}</h1>
                    </div>
                    <div class="manar-clock hidden sm:block" x-data="campusClock(@js($clockTimezone), @js($clockNow->format('H:i:s')), @js($clockOffset))">
                        <div class="manar-clock-time" x-text="time">{{ $clockNow->format('H:i:s') }}</div>
                        <div class="manar-clock-zone">
                            <span>{{ strtoupper($clockTimezone) }}</span>
                            <span class="manar-clock-offset">(<span x-text="offset">{{ $clockOffset }}</span>)</span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-3">
                    <x-language-switcher class="hidden md:flex" />

                    <a href="{{ route('notifications.index') }}" class="manar-icon-btn relative" aria-label="Notifications" x-data="{ unread: {{ $unreadCount ?? 0 }} }" @notification-received.window="unread++">
                        <template x-if="unread > 0">
                            <span class="bell-dot"></span>
                        </template>
                        <x-ui.icon name="bell" />
                        <template x-if="unread > 0">
                            <span class="absolute -right-1 -top-1 rounded-full bg-secondary px-1.5 py-0.5 text-[10px] font-bold text-white" x-text="unread"></span>
                        </template>
                    </a>


                    <a href="{{ route('chat.index') }}" class="manar-icon-btn hidden sm:flex" aria-label="Chat">
                        <x-ui.icon name="chat-bubble" />
                    </a>

                    <button type="button" class="manar-icon-btn hidden sm:flex" title="{{ __('messages.common.logout') }}" @click="logoutConfirmOpen = true">
                        <x-ui.icon name="logout" />
                    </button>

                    <a href="{{ route('settings.index') }}" class="manar-icon-btn hidden sm:flex" aria-label="Settings">
                        <x-ui.icon name="settings" />
                    </a>
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

        <div class="sc-modal-backdrop" x-show="logoutConfirmOpen" x-transition.opacity x-cloak>
            <section class="sc-modal max-w-md" role="dialog" aria-modal="true" aria-labelledby="logout-confirm-title" @click.outside="logoutConfirmOpen = false">
                <div class="sc-modal-header">
                    <div>
                        <h2 id="logout-confirm-title" class="text-lg font-black text-slate-900">{{ __('messages.auth.logout_confirm_title') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('messages.auth.logout_confirm_text') }}</p>
                    </div>
                    <button type="button" class="sc-modal-close" @click="logoutConfirmOpen = false" aria-label="{{ __('messages.common.cancel') }}">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="sc-modal-footer">
                    <button type="button" class="sc-btn sc-btn-secondary" @click="logoutConfirmOpen = false">{{ __('messages.common.cancel') }}</button>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="sc-btn sc-btn-danger">
                            <x-ui.icon name="logout" size="size-4" />
                            {{ __('messages.common.logout') }}
                        </button>
                    </form>
                </div>
            </section>
        </div>
    </div>

    <nav class="mobile-nav lg:hidden">
        @php
            $mobile = $mobileNav;
        @endphp
        @foreach ($mobile as $item)
            @php $isActive = $isItemActive($item); @endphp
            <a href="{{ $hrefFor($item) }}" class="mobile-nav-item {{ $isActive ? 'active' : '' }}">
                <x-ui.icon :name="$item['icon']" size="size-5" />
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.Realtime) {
                window.Realtime.initNotifications({{ $user?->id ?? 'null' }});
                window.Realtime.initTimetable({{ $user?->group_id ?? 'null' }});
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
