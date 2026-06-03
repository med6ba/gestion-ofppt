<x-layouts.app title="Emploi du Temps">
    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
        'groups' => $groups,
        'selectedGroupId' => $selectedGroupId,
        'selectedWeekStart' => $selectedWeekStart,
        'selectedWeekEnd' => $selectedWeekEnd,
        'isSelectedWeekActive' => $isSelectedWeekActive,
        'showActions' => true,
        'days' => $weekDays,
    ])

    <div class="mt-6 grid gap-6 xl:grid-cols-[380px_1fr]">
        <div class="grid content-start gap-6">
            <section class="sc-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Semaine de travail</h2>
                        <p class="mt-1 text-sm text-slate-500">La semaine active est visible par tous les utilisateurs.</p>
                    </div>
                    @if ($isSelectedWeekActive)
                        <span class="sc-badge bg-emerald-50 text-emerald-700">Active</span>
                    @else
                        <span class="sc-badge bg-slate-100 text-slate-600">Brouillon</span>
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

                <form method="POST" action="{{ route('timetable.active-week') }}" class="mt-3">
                    @csrf
                    <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">
                    <input type="hidden" name="week_start" value="{{ $selectedWeekStart->toDateString() }}">
                    <button class="sc-btn sc-btn-primary w-full {{ $isSelectedWeekActive ? 'opacity-70' : '' }}" @disabled($isSelectedWeekActive)>
                        {{ $isSelectedWeekActive ? 'Deja active pour tous' : 'Activer pour tous' }}
                    </button>
                </form>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Creer une seance</h2>
                <form method="POST" action="{{ route('timetable.store') }}" class="mt-4 grid gap-3">
                    @csrf
                    @include('timetable.partials.form', ['session' => null])
                    <button class="sc-btn sc-btn-primary">Enregistrer</button>
                </form>
            </section>
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
                                <div class="font-semibold">{{ $weekDays[$session->day_of_week] }} {{ $session->timeLabel() }}</div>
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
                            <div class="flex gap-2">
                                <a href="{{ route('timetable.edit', $session) }}" class="sc-btn sc-btn-secondary">Modifier</a>
                                <form method="POST" action="{{ route('timetable.destroy', $session) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="sc-btn sc-btn-danger">Supprimer</button>
                                </form>
                            </div>
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
