@props(['dark' => false])

@php
    $currentLocale = app()->getLocale();
    $locales = config('app.locale_names', [
        'fr' => 'Français',
        'ar' => 'العربية',
        'en' => 'English',
    ]);
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-1']) }}>
    @foreach ($locales as $locale => $label)
        @php $active = $currentLocale === $locale; @endphp
        <a
            href="{{ route('lang.switch', $locale) }}"
            @if ($active) aria-current="true" @endif
            class="rounded-lg px-2.5 py-1.5 text-xs font-bold transition {{ $active
                ? ($dark ? 'bg-white text-primary' : 'bg-primary text-white')
                : ($dark ? 'text-white/80 hover:bg-white/10 hover:text-white' : 'text-slate-500 hover:bg-slate-100 hover:text-primary') }}"
        >
            {{ $label }}
        </a>
    @endforeach
</div>
