@props(['user', 'size' => 'md'])

@php
    $sizeClasses = match ($size) {
        'sm' => 'size-8',
        'md' => 'size-12',
        'lg' => 'size-16',
        'xl' => 'size-24',
        default => 'size-12',
    };

    $iconSize = match ($size) {
        'sm' => 'size-4',
        'md' => 'size-6',
        'lg' => 'size-8',
        'xl' => 'size-12',
        default => 'size-6',
    };

    $genderColors = match ($user->gender) {
        'male' => 'bg-blue-100 text-blue-500',
        'female' => 'bg-pink-100 text-pink-500',
        default => 'bg-slate-100 text-slate-500',
    };

    $avatarUrl = method_exists($user, 'avatarUrl') ? $user->avatarUrl() : null;
@endphp

@if ($avatarUrl)
    <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="{{ $sizeClasses }} rounded-full object-cover shrink-0 border border-slate-200">
@else
    <div class="{{ $sizeClasses }} shrink-0 rounded-full flex items-center justify-center {{ $genderColors }} border border-slate-200">
        <svg class="{{ $iconSize }}" viewBox="0 0 24 24" fill="currentColor">
            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
        </svg>
    </div>
@endif
