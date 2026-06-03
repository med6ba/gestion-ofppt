<x-layouts.app title="Stagiaire Dashboard">
    @php
        $lateStatuses = ['late_pending', 'late_validated', 'late_rejected', 'severe_late_pending', 'severe_late_validated', 'severe_late_rejected'];
        $lateTotal = collect($lateStatuses)->sum(fn ($status) => (int) $attendanceCounts->get($status, 0));
        $statusLabels = [
            'present' => 'Present',
            'late_pending' => 'Retard declare',
            'late_validated' => 'Retard valide',
            'late_rejected' => 'Retard refuse',
            'severe_late_pending' => 'Retard important en attente',
            'severe_late_validated' => 'Retard important valide',
            'severe_late_rejected' => 'Retard important refuse',
            'absent' => 'Absent',
            'justified' => 'Justifie',
        ];
        $statusTone = fn (?string $status) => match ($status) {
            'present', 'late_validated', 'severe_late_validated', 'justified' => 'bg-emerald-100 text-emerald-700',
            'absent', 'late_rejected', 'severe_late_rejected' => 'bg-rose-100 text-rose-700',
            'late_pending', 'severe_late_pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Presence XP</div>
            <div class="mt-3 text-3xl font-bold">{{ $presenceProfile->xp_points }}</div>
            <div class="mt-1 text-xs font-semibold text-slate-500">{{ $presenceProfile->rank_level }} | streak {{ $presenceProfile->attendance_streak }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Absences</div>
            <div class="mt-3 text-3xl font-bold">{{ $attendanceCounts->get('absent', 0) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Late arrivals</div>
            <div class="mt-3 text-3xl font-bold">{{ $lateTotal }}</div>
        </div>
        <a href="{{ route('attendance.leaderboard') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">My progress</div>
            <div class="mt-3 text-3xl font-bold">{{ $presenceProfile->attendance_streak }}</div>
        </a>
    </div>

    @if ($activeLateWindows->count())
        <section class="mt-6 sc-card p-5">
            <h2 class="text-lg font-bold">Declaration de retard</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach ($activeLateWindows as $attendanceSession)
                    <form method="POST" action="{{ route('attendance.late.declare') }}" class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        @csrf
                        <input type="hidden" name="attendance_session_id" value="{{ $attendanceSession->id }}">
                        <div class="font-semibold text-amber-900">{{ $attendanceSession->timetableSession->module->name }}</div>
                        <div class="mt-1 text-sm text-amber-800">{{ $attendanceSession->timetableSession->room->code }} | {{ $attendanceSession->delayMinutes() }} min de retard</div>
                        <button class="sc-btn mt-4 border border-amber-300 bg-white text-amber-800 hover:bg-amber-100">Je suis arrive en retard</button>
                    </form>
                @endforeach
            </div>
        </section>
    @endif

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Today classes</h2>
                <a href="{{ route('attendance.check-in') }}" class="sc-btn sc-btn-primary">Check in</a>
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
                                {{ $attendance ? ($statusLabels[$attendance->status] ?? $attendance->status) : 'Non marque' }}
                            </span>
                            @if ($session->activeAttendanceSession?->isQrPhaseOpen())
                                <span class="text-xs font-semibold text-emerald-700">QR phase active</span>
                            @elseif ($session->activeAttendanceSession?->isLateDeclarationOpen() && !$attendance)
                                <span class="text-xs font-semibold text-amber-700">Retard declarable</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No classes today.</p>
                @endforelse
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Risk indicator</h2>
            <div class="mt-4 rounded-lg {{ $riskScore->level === 'High' ? 'bg-rose-50 text-rose-800' : ($riskScore->level === 'Medium' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800') }} p-4">
                <div class="text-sm font-semibold">{{ $riskScore->level }} Risk</div>
                <div class="mt-2 text-4xl font-bold">{{ $riskScore->score }}</div>
                <div class="mt-2 text-sm">{{ implode(' | ', $riskScore->reasons ?? ['No risk signals']) }}</div>
            </div>
            <a href="{{ route('attendance.leaderboard') }}" class="sc-btn sc-btn-secondary mt-4 w-full">Presence XP</a>
        </section>
    </div>

    <section class="mt-6 sc-card p-5">
        <h2 class="text-lg font-bold">Tomorrow classes</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse ($tomorrowSessions as $session)
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $session->room->code }} | {{ $session->formateur->name }}</div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No classes tomorrow.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
