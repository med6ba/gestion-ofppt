<x-layouts.app title="Statistiques des Notes">
    @php
        $format = fn ($value, string $empty = '-') => $value === null ? $empty : number_format((float) $value, 2);
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-800">Statistiques</h2>
                <p class="mt-1 text-sm text-slate-500">Analyse des moyennes publiées et des risques académiques.</p>
            </div>
            @unless(auth()->user()->isStagiaire())
                <form method="GET" class="grid gap-2 sm:grid-cols-3">
                    <select name="group_id" class="sc-input">
                        <option value="">Tous les groupes</option>
                        @foreach ($groups as $group)
                            <option value="{{ $group->id }}" @selected(($filters['group_id'] ?? '') == $group->id)>{{ $group->code }}</option>
                        @endforeach
                    </select>
                    <select name="module_id" class="sc-input">
                        <option value="">Tous les modules</option>
                        @foreach ($modules as $module)
                            <option value="{{ $module->id }}" @selected(($filters['module_id'] ?? '') == $module->id)>{{ $module->name }}</option>
                        @endforeach
                    </select>
                    <button class="sc-btn sc-btn-primary">Filtrer</button>
                </form>
            @endunless
        </div>
    </section>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sc-card p-5">
            <div class="text-sm font-semibold text-slate-500">Moyenne globale</div>
            <div class="mt-2 text-3xl font-black text-campus-700">{{ $format($globalAverage) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-semibold text-slate-500">Sous 10</div>
            <div class="mt-2 text-3xl font-black text-rose-700">{{ $belowTen->count() }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-semibold text-slate-500">Meilleur module</div>
            <div class="mt-2 text-lg font-black text-slate-800">{{ $bestModule['label'] ?? '-' }}</div>
            <div class="text-sm text-campus-700">{{ $format($bestModule['value'] ?? null) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-semibold text-slate-500">Module faible</div>
            <div class="mt-2 text-lg font-black text-slate-800">{{ $weakestModule['label'] ?? '-' }}</div>
            <div class="text-sm text-rose-700">{{ $format($weakestModule['value'] ?? null) }}</div>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="sc-card p-5">
            <h3 class="text-lg font-black text-slate-800">Moyenne par module</h3>
            <div class="mt-5 h-72"><canvas id="moduleAverageChart"></canvas></div>
        </section>
        <section class="sc-card p-5">
            <h3 class="text-lg font-black text-slate-800">Réussite par groupe</h3>
            <div class="mt-5 h-72"><canvas id="groupSuccessChart"></canvas></div>
        </section>
        <section class="sc-card p-5">
            <h3 class="text-lg font-black text-slate-800">Distribution des notes</h3>
            <div class="mt-5 h-72"><canvas id="distributionChart"></canvas></div>
        </section>
        <section class="sc-card p-5">
            <h3 class="text-lg font-black text-slate-800">Risques académiques</h3>
            <div class="mt-4 space-y-3">
                @forelse ($belowTen->take(8) as $row)
                    <a href="{{ route('profile.show', $row->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-bold text-slate-800">{{ $row->stagiaire->name }}</div>
                                <div class="text-xs text-slate-500">{{ $row->group->code }} | {{ $row->module->name }}</div>
                            </div>
                            <span class="sc-badge bg-rose-100 text-rose-700">{{ $format($row->moy_module) }}</span>
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">Aucun risque académique détecté.</p>
                @endforelse
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            const renderEvaluationStats = () => {
                if (!window.Chart) {
                    window.requestAnimationFrame(renderEvaluationStats);

                    return;
                }

                const moduleAverages = @json($moduleAverages);
                const groupSuccess = @json($groupSuccess);
                const distribution = @json($distribution);

                const barOptions = {
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true, grid: { color: 'rgba(148, 163, 184, 0.18)' } } },
                    plugins: { legend: { display: false } },
                };

                new window.Chart(document.getElementById('moduleAverageChart'), {
                    type: 'bar',
                    data: { labels: moduleAverages.map(item => item.label), datasets: [{ data: moduleAverages.map(item => item.value), backgroundColor: '#16846d' }] },
                    options: { ...barOptions, scales: { y: { beginAtZero: true, max: 20, grid: { color: 'rgba(148, 163, 184, 0.18)' } } } },
                });

                new window.Chart(document.getElementById('groupSuccessChart'), {
                    type: 'line',
                    data: { labels: groupSuccess.map(item => item.label), datasets: [{ label: 'Taux de réussite', data: groupSuccess.map(item => item.value), borderColor: '#48bfa8', backgroundColor: 'rgba(72, 191, 168, 0.28)', fill: true, tension: 0.4 }] },
                    options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } } },
                });

                new window.Chart(document.getElementById('distributionChart'), {
                    type: 'bar',
                    data: { labels: distribution.map(item => item.label), datasets: [{ data: distribution.map(item => item.value), backgroundColor: ['#ef4444', '#f59e0b', '#16846d', '#0f766e'] }] },
                    options: barOptions,
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderEvaluationStats, { once: true });
            } else {
                renderEvaluationStats();
            }
        </script>
    @endpush
</x-layouts.app>
