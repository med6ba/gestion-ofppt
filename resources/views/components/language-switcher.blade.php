@props(['dark' => false])

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.locale_names', [
        'fr' => 'Français',
        'ar' => 'العربية',
        'en' => 'English',
    ]);
    $shortLabels = ['fr' => 'FR', 'ar' => 'AR', 'en' => 'EN'];
    $currentLabel = $locales[$currentLocale] ?? strtoupper($currentLocale);
@endphp

<div
    {{ $attributes->class(['language-menu relative inline-flex']) }}
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
    @click.outside="open = false"
>
    <button
        type="button"
        class="language-menu-trigger {{ $dark ? 'language-menu-trigger-dark' : '' }}"
        :aria-expanded="open"
        aria-haspopup="menu"
        @click="open = !open"
    >
        <span class="language-menu-globe" aria-hidden="true">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <path d="M2 12h20" />
                <path d="M12 2a15.3 15.3 0 0 1 0 20" />
                <path d="M12 2a15.3 15.3 0 0 0 0 20" />
            </svg>
        </span>
        <span class="language-menu-current">{{ $currentLabel }}</span>
        <span class="language-menu-code">{{ $shortLabels[$currentLocale] ?? strtoupper($currentLocale) }}</span>
        <svg class="size-4 shrink-0 transition" :class="open ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div class="language-menu-panel" x-show="open" x-transition.origin.top.right x-cloak role="menu">
        @foreach ($locales as $locale => $label)
            @php $active = $currentLocale === $locale; @endphp
            <a
                href="{{ route('lang.switch', $locale) }}"
                role="menuitem"
                @if ($active) aria-current="true" @endif
                class="language-menu-option {{ $active ? 'active' : '' }}"
                @click="open = false"
            >
                <span class="language-menu-option-code">{{ $shortLabels[$locale] ?? strtoupper($locale) }}</span>
                <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                @if ($active)
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" />
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
