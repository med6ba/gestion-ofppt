<x-layouts.app title="Stagiaire Dashboard">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Approval status</div>
            <div class="mt-3 text-2xl font-bold capitalize">{{ auth()->user()->approval_status }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Absences</div>
            <div class="mt-3 text-3xl font-bold">{{ $attendanceCounts->get('absent', 0) }}</div>
        </div>
        <div class="sc-card p-5">
            <div class="text-sm font-medium text-slate-500">Late arrivals</div>
            <div class="mt-3 text-3xl font-bold">{{ $attendanceCounts->get('late', 0) }}</div>
        </div>
        <a href="{{ route('chat.index') }}" class="sc-card p-5 hover:bg-slate-50">
            <div class="text-sm font-medium text-slate-500">Messages</div>
            <div class="mt-3 text-3xl font-bold">{{ $unreadMessages }}</div>
        </a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Today classes</h2>
                <a href="{{ route('attendance.check-in') }}" class="sc-btn sc-btn-primary">Check in</a>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($todaySessions as $session)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                            <span class="sc-badge bg-campus-50 text-campus-700">{{ $session->room->code }}</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-500">{{ $session->formateur->name }}</div>
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
