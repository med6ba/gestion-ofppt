<x-layouts.app title="Emploi du Temps">
    @if (($weekHistory ?? collect())->isNotEmpty())
        <form method="GET" action="{{ route('timetable.mine') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div>
                <label class="sc-label">Historique des semaines</label>
                <select class="sc-input mt-1" name="week_start" onchange="this.form.submit()">
                    @foreach ($weekHistory as $historyWeek)
                        <option value="{{ $historyWeek->toDateString() }}" @selected($selectedWeekStart->isSameDay($historyWeek))>
                            Semaine {{ $historyWeek->weekOfYear }} - {{ $historyWeek->format('d/m/Y') }} au {{ $historyWeek->copy()->addDays(5)->format('d/m/Y') }}{{ $loop->first ? ' (derniere)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    @endif

    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
        'selectedWeekStart' => $selectedWeekStart,
        'selectedWeekEnd' => $selectedWeekEnd,
        'isSelectedWeekActive' => $isSelectedWeekActive,
        'days' => $weekDays,
    ])
</x-layouts.app>
