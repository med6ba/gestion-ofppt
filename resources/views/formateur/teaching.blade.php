<x-layouts.app title="Mes enseignements">
    @php
        $tabs = [
            'groups' => ['label' => 'Groupes', 'icon' => 'users-group'],
            'modules' => ['label' => 'Modules', 'icon' => 'layers'],
            'students' => ['label' => 'Stagiaires', 'icon' => 'users'],
        ];
        $riskTone = fn (?string $level) => match ($level) {
            'High' => 'bg-rose-100 text-rose-700',
            'Medium' => 'bg-amber-100 text-amber-700',
            'Low' => 'bg-emerald-100 text-emerald-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-bold">Mes classes, modules et stagiaires</h2>
                <p class="text-sm text-slate-500">Vue unifiee des enseignements relies a votre emploi du temps.</p>
            </div>
            <a href="{{ route('timetable.mine') }}" class="sc-btn sc-btn-secondary">
                <x-ui.icon name="calendar" size="size-4" />
                Emploi du temps
            </a>
        </div>

        <div class="mt-5 flex gap-2 overflow-x-auto pb-1">
            @foreach ($tabs as $key => $tab)
                <a href="{{ route('formateur.teaching', ['tab' => $key]) }}" class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg px-3 py-2 text-sm font-semibold transition {{ $activeTab === $key ? 'bg-primary text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    <x-ui.icon :name="$tab['icon']" size="size-4" />
                    {{ $tab['label'] }}
                </a>
            @endforeach
        </div>
    </section>

    @if ($activeTab === 'groups')
        <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_360px]">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Groupes enseignes</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($groups as $group)
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $group->code }} - {{ $group->name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ $group->filiere?->code ?? 'Filiere' }} | {{ $group->stagiaires->count() }} stagiaires</div>
                                </div>
                                <span class="sc-badge bg-campus-50 text-campus-700">{{ $group->capacity }} places</span>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucun groupe assigne.</p>
                    @endforelse
                </div>
            </section>

            <aside class="sc-card p-5">
                <h2 class="text-lg font-bold">Prochaines seances</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($upcomingSessions as $session)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="font-semibold">{{ $session->module->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $session->group->code }} | {{ $session->room->code }} | {{ $session->timeLabel() }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">Aucune seance planifiee.</p>
                    @endforelse
                </div>
            </aside>
        </div>
    @elseif ($activeTab === 'modules')
        <section class="mt-6 sc-card p-5">
            <h2 class="text-lg font-bold">Modules enseignes</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($modules as $module)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="font-semibold">{{ $module->code }} - {{ $module->name }}</div>
                        @if ($module->description)
                            <div class="mt-2 text-sm text-slate-500">{{ $module->description }}</div>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Aucun module assigne.</p>
                @endforelse
            </div>
        </section>
    @else
        <section class="mt-6 sc-card p-5">
            <h2 class="text-lg font-bold">Stagiaires suivis</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($students as $student)
                    <a href="{{ route('profile.show', $student) }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="truncate font-semibold">{{ $student->name }}</div>
                                <div class="mt-1 truncate text-xs text-slate-500">{{ $student->registration_number ?? 'Sans matricule' }} | {{ $student->group?->code }}</div>
                            </div>
                            <span class="sc-badge {{ $riskTone($student->riskScore?->level) }}">{{ $student->riskScore?->level ?? 'No risk' }}</span>
                        </div>
                        <div class="mt-3 text-sm font-semibold text-campus-700">{{ $student->presenceProfile?->xp_points ?? 0 }} XP</div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Aucun stagiaire approuve dans vos groupes.</p>
                @endforelse
            </div>
        </section>
    @endif
</x-layouts.app>
