@php
    $sessions = collect($sessions ?? []);
    $days = $days ?? [
        1 => 'LUNDI',
        2 => 'MARDI',
        3 => 'MERCREDI',
        4 => 'JEUDI',
        5 => 'VENDREDI',
        6 => 'SAMEDI',
    ];
    $startHour = $startHour ?? 6;
    $endHour = $endHour ?? 18;
    $hourHeight = $hourHeight ?? 96;
    $gridHeight = ($endHour - $startHour) * $hourHeight;
    $gridMinWidth = 88 + (count($days) * 190);
    $sessionsByDay = $sessions->groupBy('day_of_week');
    $palette = ['violet', 'teal', 'amber', 'rose', 'blue'];
    $toMinutes = function (string $time): int {
        [$hour, $minute] = array_pad(explode(':', substr($time, 0, 5)), 2, 0);

        return ((int) $hour * 60) + (int) $minute;
    };
@endphp

<section class="edt-panel">
    <div class="edt-toolbar">
        <div class="edt-toolbar-main">
            @isset($groups)
                <form method="GET" action="{{ route('timetable.index') }}" class="edt-group-select">
                    <span class="edt-dot"></span>
                    <select name="group_id" aria-label="Groupe" onchange="this.form.submit()">
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected((int) $selectedGroupId === $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </form>
            @else
                <div class="edt-chip">
                    <span class="edt-dot"></span>
                    <span>{{ $scheduleLabel ?? 'Emploi du temps' }}</span>
                </div>
            @endisset

            @isset($selectedWeekStart)
                <div class="edt-chip edt-week-chip {{ ($isSelectedWeekActive ?? false) ? 'edt-week-chip-active' : '' }}">
                    <x-ui.icon name="calendar" size="size-4" />
                    <span>Semaine {{ $selectedWeekStart->weekOfYear }}</span>
                    <span class="edt-week-dates">{{ $selectedWeekStart->format('d/m') }} - {{ ($selectedWeekEnd ?? $selectedWeekStart->copy()->addDays(5))->format('d/m/Y') }}</span>
                </div>
            @endisset
        </div>

        <div class="edt-view-tools">
            <x-ui.icon name="clock" size="size-4" />
            <span>{{ $sessions->count() }} seance{{ $sessions->count() > 1 ? 's' : '' }}</span>
        </div>
    </div>

    <div class="edt-grid-scroll">
        <div class="edt-grid" style="--grid-height: {{ $gridHeight }}px; --hour-height: {{ $hourHeight }}px; min-width: {{ $gridMinWidth }}px; grid-template-columns: 88px repeat({{ count($days) }}, minmax(190px, 1fr));">
            <div class="edt-time-head">
                <x-ui.icon name="clock" size="size-4" />
            </div>
            @foreach ($days as $dayName)
                <div class="edt-day-head">{{ strtoupper($dayName) }}</div>
            @endforeach

            <div class="edt-time-axis">
                @for ($hour = $startHour; $hour <= $endHour; $hour++)
                    <div class="edt-time-label" style="top: {{ ($hour - $startHour) * $hourHeight }}px">{{ $hour }}:00</div>
                @endfor
            </div>

            @foreach ($days as $dayNumber => $dayName)
                <div class="edt-day-column">
                    @foreach ($sessionsByDay->get($dayNumber, collect()) as $session)
                        @php
                            $start = max($toMinutes($session->starts_at), $startHour * 60);
                            $end = min($toMinutes($session->ends_at), $endHour * 60);
                            $top = max(0, (($start - ($startHour * 60)) / 60) * $hourHeight);
                            $height = max(46, (($end - $start) / 60) * $hourHeight);
                            $color = $palette[$session->id % count($palette)];
                        @endphp
                        @if ($end > $start)
                            <article class="edt-event edt-event-{{ $color }} {{ ($showActions ?? false) ? 'edt-event-actionable' : '' }}" style="top: {{ $top }}px; height: {{ $height }}px;">
                                <div class="truncate text-xs font-bold">{{ $session->timeLabel() }}</div>
                                <div class="mt-1 truncate text-sm font-black">{{ $session->module->name }}</div>
                                <div class="mt-1 truncate text-xs">{{ $session->group->code }} | {{ $session->room->code }}</div>
                                <div class="mt-1 truncate text-[11px] opacity-80">{{ $session->formateur->name }}</div>
                                @if ($showActions ?? false)
                                    <a href="{{ route('timetable.edit', $session) }}" class="edt-event-link">Modifier</a>
                                @endif
                            </article>
                        @endif
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</section>
