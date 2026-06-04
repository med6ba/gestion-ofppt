<x-layouts.app :title="__('messages.dashboard.surveillant_title')">
    @php
        $riskLabels = [
            'High' => __('messages.dashboard.risk_high'),
            'Medium' => __('messages.dashboard.risk_medium'),
            'Low' => __('messages.dashboard.risk_low'),
        ];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <a href="{{ route('users.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.pending_approvals') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $pendingStagiaires }}</div>
        </a>
        <a href="{{ route('attendance.reports') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.high_risk') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $riskScores->where('level', 'High')->count() }}</div>
        </a>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.today_sessions') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $todaySessions->count() }}</div>
        </div>
        <a href="{{ route('attestations.manage') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.attestations_pending') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $pendingAttestations }}</div>
        </a>
        <a href="{{ route('absences.manage') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.absences_pending') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $pendingAbsences }}</div>
        </a>
    </div>

    <section class="mt-6 sc-card p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.severe_late_review') }}</h2>
                <p class="text-sm text-slate-500">{{ __('messages.dashboard.severe_late_review_text') }}</p>
            </div>
            <span class="sc-badge bg-amber-100 text-amber-700">{{ __('messages.dashboard.pending_count', ['count' => $severeLateQueue->count()]) }}</span>
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
                                <x-confirmation-modal title="Confirmer l'approbation" message="Êtes-vous sûr de vouloir approuver ce retard ?" confirmText="{{ __('messages.common.approve') }}" cancelText="{{ __('messages.common.cancel') }}" type="primary">
                                    <button class="sc-btn border border-emerald-200 bg-emerald-50 text-emerald-700">{{ __('messages.common.approve') }}</button>
                                </x-confirmation-modal>
                            </form>
                            <form method="POST" action="{{ route('attendance.severe-late.reject', $attendance) }}" class="flex gap-2">
                                @csrf
                                <input name="rejection_reason" class="sc-input w-44" placeholder="{{ __('messages.absences.reason') }}">
                                <x-confirmation-modal title="Confirmer le refus" message="Êtes-vous sûr de vouloir refuser ce retard ?" confirmText="{{ __('messages.common.reject') }}" cancelText="{{ __('messages.common.cancel') }}" type="danger">
                                    <button class="sc-btn border border-rose-200 bg-rose-50 text-rose-700">{{ __('messages.common.reject') }}</button>
                                </x-confirmation-modal>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_severe_late_pending') }}</p>
            @endforelse
        </div>
    </section>

    <div class="mt-6 grid items-start gap-6 xl:grid-cols-[1fr_380px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.weekly_planning_snapshot') }}</h2>
                <a href="{{ route('timetable.index') }}" class="sc-btn sc-btn-secondary">{{ __('messages.dashboard.manage') }}</a>
            </div>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @forelse ($todaySessions as $session)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->group->code }}</div>
                        <div class="mt-1 text-sm text-slate-500">{{ $session->module->name }} | {{ $session->room->code }} | {{ $session->formateur->name }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_sessions_today') }}</p>
                @endforelse
            </div>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.risk_follow_up') }}</h2>
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
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.presence_xp_leaders') }}</h2>
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
                        <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_xp_data') }}</p>
                    @endforelse
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.repeated_absences') }}</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($mostAbsentStudents as $student)
                        @php
                            $studentRiskLevel = $riskLabels[$student->riskScore?->level ?? 'Low'] ?? ($student->riskScore?->level ?? 'Low');
                        @endphp
                        <a href="{{ route('profile.show', $student) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="font-semibold">{{ $student->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $student->group?->code ?? __('messages.common.no_group') }}</div>
                                </div>
                                <span class="sc-badge bg-rose-100 text-rose-700">{{ __('messages.dashboard.absent_count', ['count' => $student->absences_count]) }}</span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">{{ __('messages.dashboard.student_late_risk', ['late' => $student->late_count, 'risk' => $studentRiskLevel]) }}</div>
                        </a>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_absences_recorded') }}</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">{{ __('messages.dashboard.attendance_distribution') }}</h2>
            <div class="mt-5 h-64"><canvas id="attendanceChart"></canvas></div>
        </section>
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">{{ __('messages.dashboard.room_occupancy') }}</h2>
            <div class="mt-5 h-64"><canvas id="roomChart"></canvas></div>
        </section>
    </div>

    @push('scripts')
        <script>
            const renderSurveillantCharts = () => {
                if (!window.Chart) {
                    window.requestAnimationFrame(renderSurveillantCharts);

                    return;
                }

                const attendanceCanvas = document.getElementById('attendanceChart');
                const roomCanvas = document.getElementById('roomChart');

                if (attendanceCanvas) {
                    const attendanceData = @json($attendanceChart);

                    new window.Chart(attendanceCanvas, {
                        type: 'bar',
                        data: { labels: attendanceData.labels, datasets: [{ data: attendanceData.data, backgroundColor: ['#16846d', '#e11d48', '#f59e0b', '#3b82f6'] }] },
                        options: { maintainAspectRatio: false, plugins: { legend: { display: false } } }
                    });
                }

                if (roomCanvas) {
                    const rooms = @json($roomOccupancy);

                    new window.Chart(roomCanvas, {
                        type: 'bar',
                        data: { labels: rooms.map(item => item.room), datasets: [{ data: rooms.map(item => item.rate), backgroundColor: '#0f766e' }] },
                        options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } }
                    });
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', renderSurveillantCharts, { once: true });
            } else {
                renderSurveillantCharts();
            }
        </script>
    @endpush
</x-layouts.app>
