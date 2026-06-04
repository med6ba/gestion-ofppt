<x-layouts.app :title="__('messages.dashboard.stagiaire_title')">
    @php
        $lateStatuses = ['late_pending', 'late_validated', 'late_rejected', 'severe_late_pending', 'severe_late_validated', 'severe_late_rejected'];
        $lateTotal = collect($lateStatuses)->sum(fn ($status) => (int) $attendanceCounts->get($status, 0));
        $statusTone = fn (?string $status) => match ($status) {
            'present', 'late_validated', 'severe_late_validated', 'justified' => 'bg-emerald-100 text-emerald-700',
            'absent', 'late_rejected', 'severe_late_rejected' => 'bg-rose-100 text-rose-700',
            'late_pending', 'severe_late_pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
        $requestTone = fn (?string $status) => match ($status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            'pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
        $riskLabels = [
            'High' => __('messages.dashboard.risk_high'),
            'Medium' => __('messages.dashboard.risk_medium'),
            'Low' => __('messages.dashboard.risk_low'),
        ];
        $riskLevel = $riskLabels[$riskScore->level] ?? $riskScore->level;
        $riskReasons = $riskScore->reasons ?: [__('messages.dashboard.no_risk_signals')];
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.presence_xp') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $presenceProfile->xp_points }}</div>
            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $presenceProfile->rank_level }} | {{ __('messages.dashboard.streak', ['count' => $presenceProfile->attendance_streak]) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.absences') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $attendanceCounts->get('absent', 0) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.late_arrivals') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $lateTotal }}</div>
        </div>
        <a href="{{ route('attendance.leaderboard') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.my_progress') }}</div>
            <div class="mt-3 text-3xl font-bold">{{ $presenceProfile->attendance_streak }}</div>
        </a>
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-3">
        <a href="{{ route('stagiaire.badge') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.qr_badge') }}</div>
                    <div class="mt-2 text-xl font-black text-slate-800">{{ __('messages.dashboard.my_badge') }}</div>
                </div>
                <span class="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"><x-ui.icon name="qr" /></span>
            </div>
        </a>

        <a href="{{ route('attestations.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.attestation') }}</div>
                    <div class="mt-2 text-xl font-black text-slate-800">{{ __('messages.dashboard.request') }}</div>
                    @if ($latestAttestations->first())
                        <span class="sc-badge mt-3 {{ $requestTone($latestAttestations->first()->status) }}">{{ __('messages.status.'.$latestAttestations->first()->status) }}</span>
                    @endif
                </div>
                <span class="flex size-11 items-center justify-center rounded-lg bg-sky-50 text-sky-700"><x-ui.icon name="book" /></span>
            </div>
        </a>

        <a href="{{ route('absences.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-medium text-slate-500">{{ __('messages.dashboard.authorization') }}</div>
                    <div class="mt-2 text-xl font-black text-slate-800">{{ __('messages.dashboard.absence') }}</div>
                    @if ($latestAbsenceRequests->first())
                        <span class="sc-badge mt-3 {{ $requestTone($latestAbsenceRequests->first()->status) }}">{{ __('messages.status.'.$latestAbsenceRequests->first()->status) }}</span>
                    @endif
                </div>
                <span class="flex size-11 items-center justify-center rounded-lg bg-campus-50 text-campus-700"><x-ui.icon name="calendar" /></span>
            </div>
        </a>
    </div>

    @if ($activeLateWindows->count())
        <section class="mt-6 sc-card p-5">
            <h2 class="text-lg font-bold">{{ __('messages.dashboard.late_declaration') }}</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($activeLateWindows as $attendanceSession)
                    <form method="POST" action="{{ route('attendance.late.declare') }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        @csrf
                        <input type="hidden" name="attendance_session_id" value="{{ $attendanceSession->id }}">
                        <div class="font-semibold text-amber-900">{{ $attendanceSession->timetableSession->module->name }}</div>
                        <div class="mt-1 text-sm text-amber-800">{{ $attendanceSession->timetableSession->room->code }} | {{ __('messages.dashboard.delay_minutes', ['minutes' => $attendanceSession->delayMinutes()]) }}</div>
                        <button class="sc-btn mt-4 border border-amber-300 bg-white text-amber-800 hover:bg-amber-100">{{ __('messages.dashboard.arrived_late') }}</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">{{ __('messages.dashboard.today_classes') }}</h2>
                <a href="{{ route('attendance.check-in') }}" class="sc-btn sc-btn-primary">{{ __('messages.dashboard.check_in') }}</a>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($todaySessions as $session)
                    @php $attendance = $attendanceBySession->get($session->id); @endphp
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                            <span class="sc-badge bg-campus-50 text-campus-700">{{ $session->room->code }}</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-500">{{ $session->formateur->name }}</div>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="sc-badge {{ $statusTone($attendance?->status) }}">
                                {{ $attendance ? __('messages.attendance_status.'.$attendance->status) : __('messages.dashboard.not_marked') }}
                            </span>
                            @if ($session->activeAttendanceSession?->isQrPhaseOpen())
                                <span class="text-xs font-semibold text-emerald-700">{{ __('messages.dashboard.qr_phase_active') }}</span>
                            @elseif ($session->activeAttendanceSession?->isLateDeclarationOpen() && !$attendance)
                                <span class="text-xs font-semibold text-amber-700">{{ __('messages.dashboard.late_declarable') }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_classes_today') }}</p>
                @endforelse
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">{{ __('messages.dashboard.risk_indicator') }}</h2>
            <div class="mt-4 rounded-lg {{ $riskScore->level === 'High' ? 'bg-rose-50 text-rose-800' : ($riskScore->level === 'Medium' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800') }} p-4">
                <div class="text-sm font-semibold">{{ __('messages.dashboard.risk_level', ['level' => $riskLevel]) }}</div>
                <div class="mt-2 text-4xl font-bold">{{ $riskScore->score }}</div>
                <div class="mt-2 text-sm">{{ implode(' | ', $riskReasons) }}</div>
            </div>
            <a href="{{ route('attendance.leaderboard') }}" class="sc-btn sc-btn-secondary mt-4 w-full">{{ __('messages.dashboard.presence_xp') }}</a>
        </section>
    </div>

    <section class="mt-6 sc-card p-5">
        <h2 class="text-lg font-bold">{{ __('messages.dashboard.tomorrow_classes') }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse ($tomorrowSessions as $session)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $session->room->code }} | {{ $session->formateur->name }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('messages.dashboard.no_classes_tomorrow') }}</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
