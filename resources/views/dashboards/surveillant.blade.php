<x-layouts.app title="Surveillant Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('users.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">Pending approvals</div>
            <div class="mt-3 text-3xl font-bold">{{ $pendingStagiaires }}</div>
        </a>
        <a href="{{ route('attendance.reports') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">High risk</div>
            <div class="mt-3 text-3xl font-bold">{{ $riskScores->where('level', 'High')->count() }}</div>
        </a>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Today sessions</div>
            <div class="mt-3 text-3xl font-bold">{{ $todaySessions->count() }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Suspicious attempts</div>
            <div class="mt-3 text-3xl font-bold">{{ $suspiciousAttempts->count() }}</div>
        </div>
    </div>

    <section class="mt-6 sc-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">Retards importants a verifier</h2>
                <p class="text-sm text-slate-500">Le formateur ne peut pas valider ces cas directement.</p>
            </div>
            <span class="sc-badge bg-amber-100 text-amber-700">{{ $severeLateQueue->count() }} en attente</span>
        </div>
        <div class="mt-4 grid gap-3">
            @forelse ($severeLateQueue as $attendance)
                <div class="rounded-lg border border-slate-200 p-4">
                    <div class="grid gap-3 lg:grid-cols-[1fr_auto] lg:items-start">
                        <div>
                            <div class="font-semibold">{{ $attendance->stagiaire->name }}</div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ $attendance->stagiaire->group?->code }} | {{ $attendance->session->module->name }}
                                | {{ $attendance->session->formateur->name }} | {{ $attendance->delay_minutes }} min
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('attendance.severe-late.validate', $attendance) }}">
                                @csrf
                                <button class="sc-btn border border-emerald-200 bg-emerald-50 text-emerald-700">Validate</button>
                            </form>
                            <form method="POST" action="{{ route('attendance.severe-late.reject', $attendance) }}" class="flex gap-2">
                                @csrf
                                <input name="rejection_reason" class="sc-input w-44" placeholder="Reason">
                                <button class="sc-btn border border-rose-200 bg-rose-50 text-rose-700">Reject</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Aucun retard important en attente.</p>
            @endforelse
        </div>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Weekly planning snapshot</h2>
                <a href="{{ route('timetable.index') }}" class="sc-btn sc-btn-secondary">Manage</a>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse ($todaySessions as $session)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->group->code }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $session->module->name }} | {{ $session->room->code }} | {{ $session->formateur->name }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No sessions today.</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Risk follow-up</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($riskScores as $risk)
                        <a href="{{ route('profile.show', $risk->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $risk->stagiaire->name }}</span>
                                <span class="sc-badge {{ $risk->level === 'High' ? 'bg-rose-100 text-rose-700' : ($risk->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $risk->score }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">{{ implode(' | ', $risk->reasons ?? []) }}</div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Presence XP leaders</h2>
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
                <h2 class="text-lg font-bold">Repeated absences</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($mostAbsentStudents as $student)
                        <a href="{{ route('profile.show', $student) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $student->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $student->group?->code ?? 'No group' }}</div>
                                </div>
                                <span class="sc-badge bg-rose-100 text-rose-700">{{ $student->absences_count }} absent</span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">{{ $student->late_count }} late arrivals | {{ $student->riskScore?->level ?? 'Low' }} risk</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">No absences recorded yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Attendance distribution</h2>
            <div class="mt-5 h-64"><canvas id="attendanceChart"></canvas></div>
        </section>
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Room occupancy</h2>
            <div class="mt-5 h-64"><canvas id="roomChart"></canvas></div>
        </section>
    </div>

    @push('scripts')
        <script>
            const attendanceData = @json($attendanceChart);
            new Chart(document.getElementById('attendanceChart'), {
                type: 'bar',
                data: { labels: attendanceData.labels, datasets: [{ data: attendanceData.data, backgroundColor: ['#16846d', '#e11d48', '#f59e0b', '#3b82f6'] }] },
                options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
            });

            const rooms = @json($roomOccupancy);
            new Chart(document.getElementById('roomChart'), {
                type: 'bar',
                data: { labels: rooms.map(item => item.room), datasets: [{ data: rooms.map(item => item.rate), backgroundColor: '#0f766e' }] },
                options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
            });
        </script>
    @endpush
</x-layouts.app>
