@props(['name', 'size' => 'size-5'])

@php
    $icons = [
        'ai' => '<path d="M12 3l1.4 4.3L18 9l-4.6 1.7L12 15l-1.4-4.3L6 9l4.6-1.7L12 3Z"/><path d="M5 14l.8 2.2L8 17l-2.2.8L5 20l-.8-2.2L2 17l2.2-.8L5 14Z"/><path d="M19 14l.8 2.2L22 17l-2.2.8L19 20l-.8-2.2L16 17l2.2-.8L19 14Z"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/>',
        'calendar' => '<path d="M7 3v3"/><path d="M17 3v3"/><path d="M4 8h16"/><path d="M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/><path d="M8 12h.01"/><path d="M12 12h.01"/><path d="M16 12h.01"/><path d="M8 16h.01"/><path d="M12 16h.01"/>',
        'chart' => '<path d="M4 19V5"/><path d="M4 19h16"/><path d="M8 16v-5"/><path d="M12 16V8"/><path d="M16 16v-3"/>',
        'check' => '<path d="M9 11l2 2 4-5"/><path d="M7 3h10"/><path d="M6 6h12a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z"/>',
        'clock' => '<path d="M7 3v3"/><path d="M17 3v3"/><path d="M4 8h16"/><path d="M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/><path d="M12 12v3l2 1"/>',
        'dashboard' => '<path d="M4 5a1 1 0 0 1 1-1h5v7H4V5Z"/><path d="M14 4h5a1 1 0 0 1 1 1v3h-6V4Z"/><path d="M14 12h6v7a1 1 0 0 1-1 1h-5v-8Z"/><path d="M4 15h6v5H5a1 1 0 0 1-1-1v-4Z"/>',
        'layers' => '<path d="M12 3 3 8l9 5 9-5-9-5Z"/><path d="m3 13 9 5 9-5"/><path d="m3 17 9 5 9-5"/>',
        'logout' => '<path d="M10 6H6a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h4"/><path d="M15 16l4-4-4-4"/><path d="M19 12H9"/>',
        'menu' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'messages' => '<path d="M4 5h16v11H8l-4 4V5Z"/><path d="M8 9h8"/><path d="M8 13h5"/>',
        'qr' => '<path d="M4 4h6v6H4V4Z"/><path d="M14 4h6v6h-6V4Z"/><path d="M4 14h6v6H4v-6Z"/><path d="M14 14h2v2h-2v-2Z"/><path d="M18 14h2v6h-6v-2h4v-4Z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-8 0v2"/><path d="M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/><path d="M20 21v-2a3 3 0 0 0-2-2.8"/><path d="M18 4.3a3 3 0 0 1 0 5.4"/>',
    ];
@endphp

<svg {{ $attributes->class($size) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $icons[$name] ?? $icons['dashboard'] !!}
</svg>
