<x-layouts.app title="Mon Emploi du Temps">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">{{ $scheduleLabel }}</h1>
        </div>

        @include('timetable.partials.grid', [
            'sessions' => $gridSessions,
            'scheduleLabel' => $scheduleLabel,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'isSelectedWeekActive' => true,
            'showActions' => false,
            'days' => $weekDays,
            'weekHistory' => $weekHistory,
            'weekFormAction' => route('timetable.mine'),
        ])
    </div>
</x-layouts.app>
