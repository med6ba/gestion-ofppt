<x-layouts.app title="Profile">
    <div class="grid gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="sc-card p-5">
            <h2 class="text-xl font-bold">{{ $profile->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $profile->roleLabel() }}{{ $profile->group ? ' | '.$profile->group->code : '' }}</p>
            <div class="mt-5 space-y-3 text-sm">
                <div>
                    <div class="sc-label">Email</div>
                    <div class="mt-1">{{ $profile->email }}</div>
                </div>
                <div>
                    <div class="sc-label">Status</div>
                    <div class="mt-1 capitalize">{{ $profile->approval_status }}</div>
                </div>
                @if ($profile->isStagiaire() && $profile->riskScore)
                    <div class="rounded-lg {{ $profile->riskScore->level === 'High' ? 'bg-rose-50 text-rose-800' : ($profile->riskScore->level === 'Medium' ? 'bg-amber-50 text-amber-800' : 'bg-emerald-50 text-emerald-800') }} p-4">
                        <div class="text-sm font-semibold">{{ $profile->riskScore->level }} Risk</div>
                        <div class="mt-1 text-3xl font-bold">{{ $profile->riskScore->score }}</div>
                        <div class="mt-2 text-xs">{{ implode(' | ', $profile->riskScore->reasons ?? []) }}</div>
                    </div>
                @endif
            </div>
        </aside>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Attendance history</h2>
            @if ($profile->isStagiaire())
                <div class="mt-4 grid gap-3">
                    @forelse ($profile->attendances->sortByDesc('marked_at') as $attendance)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="font-semibold">{{ $attendance->session->module->name }}</div>
                                <span class="sc-badge {{ $attendance->status === 'absent' ? 'bg-rose-100 text-rose-700' : ($attendance->status === 'late' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $attendance->status }}</span>
                            </div>
                            <div class="mt-1 text-sm text-slate-500">{{ $attendance->session->timeLabel() }} | {{ $attendance->method }} | {{ $attendance->marked_at?->format('Y-m-d H:i') }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No attendance records yet.</p>
                    @endforelse
                </div>
            @else
                <p class="mt-4 text-sm text-slate-500">Attendance history applies to stagiaires.</p>
            @endif
        </section>
    </div>
</x-layouts.app>
