<x-layouts.app title="Attendance">
    <section class="sc-card p-5">
        <h2 class="text-lg font-bold">Today sessions</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @forelse ($todaySessions as $session)
                <a href="{{ route('attendance.show', $session) }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="font-semibold">{{ $session->timeLabel() }} - {{ $session->module->name }}</div>
                        <span class="sc-badge bg-campus-50 text-campus-700">{{ $session->group->code }}</span>
                    </div>
                    <div class="mt-1 text-sm text-slate-500">{{ $session->room->code }}{{ $session->activeQrSession ? ' | QR/code active' : '' }}</div>
                </a>
            @empty
                <p class="text-sm text-slate-500">No sessions today.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-6 sc-card p-5">
        <h2 class="text-lg font-bold">Recent sessions</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            @foreach ($recentSessions as $session)
                <a href="{{ route('attendance.show', $session) }}" class="rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                    <div class="font-semibold">{{ $session->module->name }}</div>
                    <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} | {{ $session->timeLabel() }}</div>
                </a>
            @endforeach
        </div>
    </section>
</x-layouts.app>
