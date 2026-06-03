<x-layouts.app title="Emploi du Temps">
    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
    ])
</x-layouts.app>
