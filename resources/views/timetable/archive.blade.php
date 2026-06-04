<x-layouts.app title="Archives des Emplois du Temps">
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">Archives</h1>
            <a href="{{ auth()->user()->isSurveillant() || auth()->user()->isDirecteur() ? route('timetable.index') : route('timetable.mine') }}" class="sc-btn sc-btn-secondary">
                Retour à l'emploi du temps
            </a>
        </div>

        @if ($groups->isNotEmpty())
            <form method="GET" action="{{ route('timetable.archive') }}" class="flex flex-wrap gap-3">
                <select name="group_id" class="sc-input !w-auto" onchange="this.form.submit()">
                    <option value="">Tous les groupes</option>
                    @foreach ($groups as $group)
                        <option value="{{ $group->id }}" @selected(request('group_id') == $group->id)>{{ $group->code }} - {{ $group->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif

        <div class="grid gap-4">
            @forelse ($timetables as $wt)
                <div class="sc-card flex flex-wrap items-center justify-between gap-4 p-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="sc-badge bg-slate-200 text-slate-600">S{{ $wt->week_start_date->weekOfYear }}</span>
                            <h2 class="text-lg font-bold">{{ $wt->group->code }} — {{ $wt->group->name }}</h2>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">
                            Semaine du {{ $wt->week_start_date->format('d/m/Y') }} au {{ $wt->week_end_date->format('d/m/Y') }}
                        </p>
                        @if ($wt->title)
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $wt->title }}</p>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ auth()->user()->isSurveillant() || auth()->user()->isDirecteur() ? route('timetable.index', ['group_id' => $wt->group_id, 'week_start' => $wt->week_start_date->toDateString()]) : route('timetable.mine', ['week_start' => $wt->week_start_date->toDateString()]) }}" class="sc-btn sc-btn-secondary">Voir</a>
                    </div>
                </div>
            @empty
                <div class="sc-card p-10 text-center text-slate-500">
                    <svg class="mx-auto mb-3 size-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
                    <p>Aucune archive trouvée.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-4">
            {{ $timetables->links() }}
        </div>
    </div>
</x-layouts.app>
