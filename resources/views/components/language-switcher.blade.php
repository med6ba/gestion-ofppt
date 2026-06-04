@props(['dark' => false])

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.locale_names', [
        'fr' => 'Français',
        'ar' => 'العربية',
        'en' => 'English',
    ]);
    $flags = ['fr' => '🇫🇷', 'ar' => '🇲🇦', 'en' => '🇺🇸'];
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
        <span class="text-xl leading-none">{{ $flags[$currentLocale] ?? '🌐' }}</span>
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
                <span class="text-xl leading-none">{{ $flags[$locale] ?? '🌐' }}</span>
                @if ($active)
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m5 12 4 4L19 6" />
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
