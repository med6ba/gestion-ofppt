<x-layouts.app title="Emploi du Temps">
    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
        'selectedWeekStart' => $selectedWeekStart,
        'selectedWeekEnd' => $selectedWeekEnd,
        'isSelectedWeekActive' => $isSelectedWeekActive,
        'days' => [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ],
    ])
</x-layouts.app>
