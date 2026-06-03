<x-layouts.app title="Emploi du Temps">
    @php
        $canManageTimetable = auth()->user()->isSurveillant();
    @endphp

    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
        'groups' => $groups,
        'selectedGroupId' => $selectedGroupId,
        'selectedWeekStart' => $selectedWeekStart,
        'selectedWeekEnd' => $selectedWeekEnd,
        'isSelectedWeekActive' => $isSelectedWeekActive,
        'showActions' => $canManageTimetable,
        'days' => $weekDays,
    ])

    <div class="mt-6 grid gap-6 xl:grid-cols-[380px_1fr]">
        <div class="grid content-start gap-6">
            <section class="sc-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Semaine de travail</h2>
                    </div>
                    @if ($isSelectedWeekLatest)
                        <span class="sc-badge bg-emerald-50 text-emerald-700">Derniere semaine</span>
                    @else
                        <span class="sc-badge bg-slate-100 text-slate-600">Historique</span>
                    @endif
                </div>

                <form method="GET" action="{{ route('timetable.index') }}" class="mt-4 grid gap-3">
                    <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">
                    <div>
                        <label class="sc-label">Semaine a gerer</label>
                        <input class="sc-input mt-1" name="week_start" type="date" value="{{ $selectedWeekStart->toDateString() }}" required>
                    </div>
                    <button class="sc-btn sc-btn-secondary">Afficher la semaine</button>
                </form>

                @if ($weekHistory->isNotEmpty())
                    <form method="GET" action="{{ route('timetable.index') }}" class="mt-3 grid gap-2">
                        <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">
                        <label class="sc-label">Historique des semaines</label>
                        <select class="sc-input" name="week_start" onchange="this.form.submit()">
                            @foreach ($weekHistory as $historyWeek)
                                <option value="{{ $historyWeek->toDateString() }}" @selected($selectedWeekStart->isSameDay($historyWeek))>
                                    Semaine {{ $historyWeek->weekOfYear }} - {{ $historyWeek->format('d/m/Y') }} au {{ $historyWeek->copy()->addDays(5)->format('d/m/Y') }}{{ $loop->first ? ' (derniere)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </section>

            @if ($canManageTimetable)
                <section class="sc-card p-5">
                    <h2 class="text-lg font-bold">Ajouter une seance</h2>
                    <form method="POST" action="{{ route('timetable.store') }}" class="mt-4 grid gap-3">
                        @csrf
                        @include('timetable.partials.form', ['session' => null])
                        <button class="sc-btn sc-btn-primary">Enregistrer</button>
                    </form>
                </section>
            @endif
        </div>

        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Seances de la semaine</h2>
                    <p class="text-sm text-slate-500">Les conflits de salle, groupe et formateur sont bloques avant sauvegarde.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($sessions as $session)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $weekDays[$session->day_of_week] ?? 'Jour '.$session->day_of_week }} {{ $session->timeLabel() }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} | {{ $session->module->name }} | {{ $session->room->code }} | {{ $session->formateur->name }}</div>
                                <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-400">
                                    <span>{{ $session->starts_on->format('Y-m-d') }} au {{ $session->ends_on->format('Y-m-d') }}</span>
                                    @if ($session->week_number)
                                        <span class="font-semibold text-slate-500">S{{ $session->week_number }}</span>
                                    @endif
                                    @if ($session->status === 'changed')
                                        <span class="font-semibold text-amber-600">Modifiee</span>
                                    @endif
                                </div>
                            </div>
                            @if ($canManageTimetable)
                                <div class="flex gap-2">
                                    <a href="{{ route('timetable.edit', $session) }}" class="sc-btn sc-btn-secondary">Modifier</a>
                                    <form method="POST" action="{{ route('timetable.destroy', $session) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="sc-btn sc-btn-danger">Supprimer</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aucune seance pour cette semaine.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $sessions->links() }}</div>
        </section>
    </div>
</x-layouts.app>
