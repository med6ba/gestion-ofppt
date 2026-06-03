<x-layouts.app title="Attendance Reports">
    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([
            ['label' => 'Attendance rate', 'value' => $attendanceSummary['attendanceRate'].'%', 'tone' => 'text-campus-700 bg-campus-50 border-campus-100'],
            ['label' => 'Present', 'value' => $attendanceSummary['present'], 'tone' => 'text-emerald-700 bg-emerald-50 border-emerald-100'],
            ['label' => 'Absent', 'value' => $attendanceSummary['absent'], 'tone' => 'text-rose-700 bg-rose-50 border-rose-100'],
            ['label' => 'Late', 'value' => $attendanceSummary['late'], 'tone' => 'text-amber-700 bg-amber-50 border-amber-100'],
            ['label' => 'Justified', 'value' => $attendanceSummary['justified'], 'tone' => 'text-blue-700 bg-blue-50 border-blue-100'],
        ] as $item)
            <div class="rounded-lg border p-4 {{ $item['tone'] }}">
                <div class="text-xs font-semibold uppercase opacity-80">{{ $item['label'] }}</div>
                <div class="mt-2 text-2xl font-bold">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Recent attendance</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($recentAttendances as $attendance)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="font-semibold">{{ $attendance->stagiaire->name }}</div>
                            <span class="sc-badge {{ $attendance->status === 'absent' ? 'bg-rose-100 text-rose-700' : ($attendance->status === 'late' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $attendance->status }}</span>
                        </div>
                        <div class="mt-1 text-sm text-slate-500">{{ $attendance->session->module->name }} | {{ $attendance->session->room->code }} | {{ $attendance->method }}</div>
                    </div>
                @endforeach
            </div>
            <div class="mt-4">{{ $recentAttendances->links() }}</div>
        </section>

        <aside class="space-y-6">
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
                        <p class="text-sm text-slate-500">No attendance history yet.</p>
                    @endforelse
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Students at risk</h2>
                <div class="mt-4 space-y-3">
                    @foreach ($riskScores as $risk)
                        <a href="{{ route('profile.show', $risk->stagiaire) }}" class="block rounded-lg border border-slate-200 p-3 hover:bg-slate-50">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold">{{ $risk->stagiaire->name }}</span>
                                <span class="sc-badge {{ $risk->level === 'High' ? 'bg-rose-100 text-rose-700' : ($risk->level === 'Medium' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">{{ $risk->level }}</span>
                            </div>
                            <div class="mt-1 text-xs text-slate-500">{{ implode(' | ', $risk->reasons ?? []) }}</div>
                        </a>
                    @endforeach
                </div>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Suspicious attempts</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($attempts as $attempt)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="font-semibold">{{ $attempt->stagiaire?->name ?? 'Unknown' }}</div>
                            <div class="mt-1 text-sm text-slate-500">{{ $attempt->reason }} | {{ $attempt->ip_address }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No suspicious attempts.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>
</x-layouts.app>
