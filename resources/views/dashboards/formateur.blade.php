<x-layouts.app title="Formateur Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Today sessions</div>
            <div class="mt-3 text-3xl font-bold">{{ $todaySessions->count() }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Groups taught</div>
            <div class="mt-3 text-3xl font-bold">{{ $groups->count() }}</div>
        </div>
        <a href="{{ route('chat.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">Message threads</div>
            <div class="mt-3 text-3xl font-bold">{{ $unreadMessages }}</div>
        </a>
        <a href="{{ route('attendance.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">Attendance actions</div>
            <div class="mt-3 text-3xl font-bold">{{ $todaySessions->count() }}</div>
        </a>
        <a href="{{ route('attendance.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">Retards a valider</div>
            <div class="mt-3 text-3xl font-bold">{{ $pendingLateCount }}</div>
        </a>
    </div>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Today sessions</h2>
                <a href="{{ route('attendance.index') }}" class="sc-btn sc-btn-primary">Open attendance</a>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($todaySessions as $session)
                    <a href="{{ route('attendance.show', $session) }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                            <span class="sc-badge bg-campus-50 text-campus-700">{{ $session->group->code }}</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-500">
                            {{ $session->room->code }}
                            {{ $session->activeQrSession ? ' | QR/code active' : '' }}
                            {{ $session->activeAttendanceSession ? ' | appel '.$session->activeAttendanceSession->status : '' }}
                        </div>
                    </a>
                @empty
                    <p class="text-sm text-slate-500">No sessions today.</p>
                @endforelse
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Next session</h2>
            @if ($nextSession)
                <div class="mt-4 rounded-lg bg-campus-50 p-4">
                    <div class="text-sm font-semibold text-campus-700">{{ $nextSession->timeLabel() }}</div>
                    <div class="mt-2 text-xl font-bold">{{ $nextSession->module->name }}</div>
                    <div class="mt-1 text-sm text-slate-600">{{ $nextSession->group->code }} | {{ $nextSession->room->code }}</div>
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">No next session today.</p>
            @endif
        </section>
    </div>

    <section class="mt-6 sc-card p-5">
        <h2 class="text-lg font-bold">Absence rate per group</h2>
        <div class="mt-5 h-64"><canvas id="groupAbsenceChart"></canvas></div>
    </section>

    <section class="mt-6 sc-card p-5">
        <div class="flex items-center justify-between gap-3">
            <h2 class="text-lg font-bold">Podium group leaders</h2>
            <a href="{{ route('attendance.leaderboard') }}" class="sc-btn sc-btn-secondary">Open</a>
        </div>
        <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @forelse ($leaderboard as $profile)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-semibold">{{ $profile->stagiaire->name }}</div>
                    <div class="mt-1 text-xs text-slate-500">{{ $profile->stagiaire->group?->code }} | {{ $profile->rank_level }}</div>
                    <div class="mt-2 text-xl font-bold text-campus-700">{{ $profile->xp_points }} XP</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No XP data yet.</p>
            @endforelse
        </div>
    </section>

    @push('scripts')
        <script>
            const renderFormateurCharts = () => {
                if (!window.Chart) {
                    window.requestAnimationFrame(renderFormateurCharts);

                    return;
                }

                const groupAbsenceCanvas = document.getElementById('groupAbsenceChart');

                if (!groupAbsenceCanvas) {
                    return;
                }

                const groupRates = @json($groupAbsenceRates);

                new window.Chart(groupAbsenceCanvas, {
                    type: 'bar',
                    data: { labels: groupRates.map(item => item.label), datasets: [{ label: 'Absence %', data: groupRates.map(item => item.rate), backgroundColor: '#16846d' }] },
                    options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderFormateurCharts, { once: true });
            } else {
                renderFormateurCharts();
            }
        </script>
    @endpush
</x-layouts.app>
