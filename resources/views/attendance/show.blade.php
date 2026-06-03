<x-layouts.app title="Mark Attendance">
    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="sc-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">{{ $session->module->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $session->group->code }} | {{ $session->room->code }} | {{ $session->timeLabel() }}</p>
                </div>
                <a href="{{ route('attendance.index') }}" class="sc-btn sc-btn-secondary">Back</a>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Attendance rate</div>
                    <div class="mt-2 text-2xl font-bold text-campus-700">{{ $attendanceSummary['attendanceRate'] }}%</div>
                </div>
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                    <div class="text-xs font-semibold uppercase text-emerald-700">Present</div>
                    <div class="mt-2 text-2xl font-bold text-emerald-800">{{ $attendanceSummary['present'] }}</div>
                </div>
                <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                    <div class="text-xs font-semibold uppercase text-rose-700">Absent</div>
                    <div class="mt-2 text-2xl font-bold text-rose-800">{{ $attendanceSummary['absent'] }}</div>
                </div>
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <div class="text-xs font-semibold uppercase text-amber-700">Late / unmarked</div>
                    <div class="mt-2 text-2xl font-bold text-amber-800">{{ $attendanceSummary['late'] }} / {{ $attendanceSummary['missing'] }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('attendance.manual.store', $session) }}" class="mt-5">
                @csrf
                <div class="grid gap-3">
                    @foreach ($students as $student)
                        @php $current = $attendanceByStudent->get($student->id)?->status ?? 'present'; @endphp
                        <div class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="font-semibold">{{ $student->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $student->registration_number }} {{ $student->riskScore ? '| '.$student->riskScore->level.' risk' : '' }}</div>
                                </div>
                                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                                    @foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'justified' => 'Justified'] as $value => $label)
                                        <label class="cursor-pointer">
                                            <input class="peer sr-only" type="radio" name="attendance[{{ $student->id }}]" value="{{ $value }}" @checked($current === $value)>
                                            <span class="block rounded-lg border border-slate-200 px-3 py-2 text-center text-xs font-semibold peer-checked:border-campus-600 peer-checked:bg-campus-50 peer-checked:text-campus-700">{{ $label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button class="sc-btn sc-btn-primary mt-5">Save attendance</button>
            </form>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">QR / code attendance</h2>
                <form method="POST" action="{{ route('attendance.qr.generate', $session) }}" class="mt-4">
                    @csrf
                    <button class="sc-btn sc-btn-primary w-full">Generate QR Attendance</button>
                </form>

                @if ($qrSession)
                    <div class="mt-5 rounded-lg bg-slate-50 p-4 text-center">
                        <img class="mx-auto h-56 w-56 rounded-lg bg-white p-2" src="{{ $qrDataUri }}" alt="QR attendance code">
                        <div class="mt-4 text-sm text-slate-500">Fallback code</div>
                        <div class="mt-1 text-4xl font-bold tracking-widest text-campus-700">{{ $qrSession->short_code }}</div>
                        <div class="mt-2 text-xs text-slate-500">Expires {{ $qrSession->expires_at->diffForHumans() }}</div>
                        <div class="mt-3 rounded-lg border border-emerald-200 bg-white px-3 py-2 text-xs font-semibold text-emerald-700">Authenticated OFPPT network required</div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-slate-500">Generate a QR/code for stagiaires to check in from their accounts.</p>
                @endif
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Validation rules</h2>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p>Approved stagiaire account required.</p>
                    <p>Correct group required.</p>
                    <p>Token/code must be valid and unexpired.</p>
                    <p>Campus IP range is checked server-side.</p>
                    <div class="rounded-lg bg-slate-50 p-3 text-xs">
                        <div class="font-semibold text-slate-700">Configured network ranges</div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @forelse ($allowedNetworks as $range)
                                <span class="sc-badge bg-white text-slate-600">{{ $range }}</span>
                            @empty
                                <span class="text-slate-500">No range configured yet.</span>
                            @endforelse
                        </div>
                    </div>
                </div>
            </section>
        </aside>
    </div>
</x-layouts.app>
