<x-layouts.app title="Directeur Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['label' => 'Total stagiaires', 'value' => $stats['stagiaires']],
            ['label' => 'Formateurs', 'value' => $stats['formateurs']],
            ['label' => 'Active groups', 'value' => $stats['groups']],
            ['label' => 'Today sessions', 'value' => $stats['todaySessions']],
        ] as $card)
            <div class="sc-card p-5">
                <div class="text-sm font-medium text-slate-500">{{ $card['label'] }}</div>
                <div class="mt-3 text-3xl font-bold">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_360px]">
        <div class="space-y-6">
            <section class="sc-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">Attendance overview</h2>
                        <p class="text-sm text-slate-500">Global absence rate: {{ $stats['absenceRate'] }}%</p>
                    </div>
                    <span class="sc-badge bg-rose-100 text-rose-700">{{ $stats['suspiciousAttempts'] }} suspicious</span>
                </div>
                <div class="mt-5 h-72">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="sc-card p-5">
                    <h2 class="text-lg font-bold">Today planning</h2>
                    <div class="mt-4 grid gap-3">
                        @forelse ($todaySessions as $session)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                                    <span class="sc-badge bg-campus-50 text-campus-700">{{ $session->room->code }}</span>
                                </div>
                                <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} with {{ $session->formateur->name }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No sessions today.</p>
                        @endforelse
                    </div>
                </section>

                <section class="sc-card p-5">
                    <h2 class="text-lg font-bold">Room occupancy</h2>
                    <div class="mt-5 h-64">
                        <canvas id="roomChart"></canvas>
                    </div>
                </section>
            </div>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Correction audit logs summary</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($auditLogs as $log)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="font-semibold">{{ $log->stagiaire->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $log->old_status ?? 'none' }} -> {{ $log->new_status }} | {{ $log->changedBy->name }}</div>
                            <div class="mt-1 text-sm text-slate-600">{{ $log->reason }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No corrections yet.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Students at risk</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($riskScores as $risk)
                        <a href="{{ route('profile.show', $risk->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $risk->stagiaire->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $risk->stagiaire->group?->code }}</div>
                                </div>
                                <span class="sc-badge {{ $risk->level === 'High' ? 'bg-rose-100 text-rose-700' : ($risk->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $risk->level }}</span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">{{ implode(' | ', $risk->reasons ?? []) }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No risk scores yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="sc-card p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">Podium overview</h2>
                    <a href="{{ route('attendance.leaderboard') }}" class="sc-btn sc-btn-secondary">Open</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($topProfiles as $profile)
                        <a href="{{ route('profile.show', $profile->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold">{{ $profile->stagiaire->name }}</span>
                                <span class="sc-badge bg-campus-50 text-campus-700">{{ $profile->xp_points }} XP</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">{{ $profile->stagiaire->group?->code }} | {{ $profile->rank_level }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No XP data yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Most absent students</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($mostAbsentStudents as $student)
                        <a href="{{ route('profile.show', $student) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $student->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $student->group?->code ?? 'No group' }}</div>
                                </div>
                                <span class="sc-badge bg-rose-100 text-rose-700">{{ $student->absences_count }}</span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">{{ $student->late_count }} late arrivals | administrative follow-up only</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No absences recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    @push('scripts')
        <script>
            const renderDirecteurCharts = () => {
                if (!window.Chart) {
                    window.requestAnimationFrame(renderDirecteurCharts);

                    return;
                }

                const attendanceCanvas = document.getElementById('attendanceChart');
                const roomCanvas = document.getElementById('roomChart');

                if (attendanceCanvas) {
                    const attendanceData = @json($attendanceTrendChart);

                    new window.Chart(attendanceCanvas, {
                        type: 'line',
                        data: {
                            labels: attendanceData.labels,
                            datasets: [
                                {
                                    label: 'Présences',
                                    data: attendanceData.present,
                                    borderColor: '#48bfa8',
                                    backgroundColor: 'rgba(72, 191, 168, 0.28)',
                                    fill: true,
                                    tension: 0.42,
                                    borderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: '#48bfa8',
                                },
                                {
                                    label: 'Absences',
                                    data: attendanceData.absent,
                                    borderColor: '#83d7c7',
                                    backgroundColor: 'rgba(131, 215, 199, 0.48)',
                                    fill: true,
                                    tension: 0.42,
                                    borderWidth: 2,
                                    pointRadius: 3,
                                    pointHoverRadius: 5,
                                    pointBackgroundColor: '#83d7c7',
                                },
                            ],
                        },
                        options: {
                            maintainAspectRatio: false,
                            interaction: { intersect: false, mode: 'index' },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: { boxWidth: 28, boxHeight: 8, usePointStyle: false },
                                },
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                    ticks: { color: '#64748b', font: { size: 11 } },
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: 'rgba(148, 163, 184, 0.18)' },
                                    ticks: { color: '#64748b', precision: 0 },
                                },
                            },
                        },
                    });
                }

                if (roomCanvas) {
                    const rooms = @json($roomOccupancy);

                    new window.Chart(roomCanvas, {
                        type: 'bar',
                        data: { labels: rooms.map(item => item.room), datasets: [{ label: 'Usage %', data: rooms.map(item => item.rate), backgroundColor: '#16846d' }] },
                        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
                    });
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderDirecteurCharts, { once: true });
            } else {
                renderDirecteurCharts();
            }
        </script>
    @endpush
</x-layouts.app>
