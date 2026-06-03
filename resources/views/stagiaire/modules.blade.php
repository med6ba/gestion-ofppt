<x-layouts.app title="Mes Modules">
    <section class="sc-card p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-lg font-bold">Mes modules</h2>
                <p class="mt-1 text-sm text-slate-500">{{ $group?->name ?? 'Mon groupe' }}</p>
            </div>
            <a href="{{ route('timetable.mine') }}" class="sc-btn sc-btn-secondary">
                <x-ui.icon name="calendar" size="size-4" />
                Emploi du temps
            </a>
        </div>
    </section>

    <section class="mt-6 sc-card p-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($modules as $entry)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">{{ $entry['module']->name }}</div>
                            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $entry['module']->code }}</div>
                        </div>
                        <span class="sc-badge bg-campus-50 text-campus-700">{{ $entry['sessions']->count() }} seance{{ $entry['sessions']->count() > 1 ? 's' : '' }}</span>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <div>
                            <span class="font-semibold text-slate-700">Formateur:</span>
                            {{ $entry['formateurs']->pluck('name')->join(' / ') ?: '-' }}
                        </div>
                        <div>
                            <span class="font-semibold text-slate-700">Salles:</span>
                            {{ $entry['rooms']->join(' / ') ?: '-' }}
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun module planifie pour la semaine active.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
