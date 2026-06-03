<x-layouts.app title="Mark Attendance">
    @php
        $statusLabels = [
            'pending' => 'En attente',
            'present' => 'Present',
            'absent' => 'Absent',
            'late_pending' => 'Retard declare',
            'late_validated' => 'Retard valide',
            'late_rejected' => 'Retard refuse',
            'severe_late_pending' => 'Retard important en attente',
            'severe_late_validated' => 'Retard important valide',
            'severe_late_rejected' => 'Retard important refuse',
            'justified' => 'Justifie',
        ];
        $statusTone = fn (?string $status) => match ($status) {
            'present', 'late_validated', 'severe_late_validated', 'justified' => 'bg-emerald-100 text-emerald-700',
            'absent', 'late_rejected', 'severe_late_rejected' => 'bg-rose-100 text-rose-700',
            'late_pending', 'severe_late_pending', 'pending' => 'bg-amber-100 text-amber-700',
            default => 'bg-slate-100 text-slate-700',
        };
        $qrPhaseOpen = $attendanceSession?->isQrPhaseOpen() ?? false;
        $qrClosed = $attendanceSession && !$qrPhaseOpen;
    @endphp

    <div class="grid gap-6 xl:grid-cols-[1fr_380px]">
        <section class="sc-card p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">{{ $session->module->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $session->group->code }} | {{ $session->room->code }} | {{ $session->timeLabel() }}</p>
                    @if ($attendanceSession)
                        <p class="mt-1 text-xs text-slate-500">
                            Appel demarre a {{ $attendanceSession->actual_started_at->format('H:i') }}
                            | QR {{ $attendanceSession->qr_phase_minutes }} min
                            | retard normal jusqu'a {{ $attendanceSession->normal_late_until_minutes }} min
                            | retard important jusqu'a {{ $attendanceSession->severe_late_until_minutes }} min
                        </p>
                    @endif
                </div>
                <a href="{{ route('attendance.index') }}" class="sc-btn sc-btn-secondary">Back</a>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
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
                    <div class="text-xs font-semibold uppercase text-amber-700">Late / pending</div>
                    <div class="mt-2 text-2xl font-bold text-amber-800">{{ $attendanceSummary['late'] }} / {{ $attendanceSummary['pending'] }}</div>
                </div>
                <div class="rounded-lg border border-slate-200 bg-white p-4">
                    <div class="text-xs font-semibold uppercase text-slate-500">Unmarked</div>
                    <div class="mt-2 text-2xl font-bold text-slate-800">{{ $attendanceSummary['missing'] }}</div>
                </div>
            </div>

            <section class="mt-6 rounded-lg border border-slate-200 p-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold">Retards a valider</h3>
                        <p class="text-sm text-slate-500">Les declarations stagiaires ne sont jamais acceptees automatiquement.</p>
                    </div>
                    @if ($latePending->count())
                        <span class="sc-badge bg-amber-100 text-amber-700">{{ $latePending->count() }} en attente</span>
                    @endif
                </div>

                @if ($latePending->count())
                    <form method="POST" action="{{ route('attendance.late.bulk-validate', $session) }}" class="mt-4 space-y-3">
                        @csrf
                        @foreach ($latePending as $attendance)
                            <div class="rounded-lg border border-slate-200 p-3">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <label class="flex min-w-0 items-start gap-3">
                                        <input type="checkbox" name="attendance_ids[]" value="{{ $attendance->id }}" class="mt-1 rounded border-slate-300 text-primary">
                                        <span class="min-w-0">
                                            <span class="block font-semibold">{{ $attendance->stagiaire->name }}</span>
                                            <span class="block text-xs text-slate-500">{{ $attendance->check_in_at?->format('H:i') }} | {{ $attendance->delay_minutes }} min | {{ $statusLabels[$attendance->status] }}</span>
                                        </span>
                                    </label>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="submit" formaction="{{ route('attendance.late.validate', [$session, $attendance]) }}" class="sc-btn border border-emerald-200 bg-emerald-50 text-emerald-700">Validate</button>
                                        <button type="submit" formaction="{{ route('attendance.late.reject', [$session, $attendance]) }}" class="sc-btn border border-rose-200 bg-rose-50 text-rose-700">Reject</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        <div class="grid gap-3 rounded-lg bg-slate-50 p-3 md:grid-cols-[1fr_auto_auto]">
                            <input name="rejection_reason" class="sc-input" placeholder="Reason required for rejection">
                            <button class="sc-btn border border-emerald-200 bg-emerald-50 text-emerald-700">Validate selected</button>
                            <button formaction="{{ route('attendance.late.bulk-reject', $session) }}" class="sc-btn border border-rose-200 bg-rose-50 text-rose-700">Reject selected</button>
                        </div>
                    </form>
                @else
                    <p class="mt-4 text-sm text-slate-500">Aucun retard normal en attente.</p>
                @endif

                @if ($severeLatePending->count())
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                        Retard important - validation par Surveillant General requise.
                        <div class="mt-2 space-y-1">
                            @foreach ($severeLatePending as $attendance)
                                <div>{{ $attendance->stagiaire->name }} | {{ $attendance->delay_minutes }} min</div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </section>

            <section class="mt-6 rounded-lg border border-slate-200 p-4">
                <h3 class="font-bold">Presence list</h3>
                <form method="POST" action="{{ route('attendance.manual.store', $session) }}" class="mt-4">
                    @csrf
                    <div class="grid gap-3">
                        @foreach ($students as $student)
                            @php
                                $record = $attendanceByStudent->get($student->id);
                                $current = $record?->status ?? 'absent';
                            @endphp
                            <div class="rounded-lg border border-slate-200 p-4">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <div class="font-semibold">{{ $student->name }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $student->registration_number }}
                                            {{ $student->riskScore ? '| '.$student->riskScore->level.' risk' : '' }}
                                            {{ $student->presenceProfile ? '| '.$student->presenceProfile->xp_points.' XP' : '' }}
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="sc-badge {{ $statusTone($record?->status) }}">{{ $record ? $statusLabels[$record->status] : 'Aucun scan' }}</span>
                                        @foreach (['present' => 'Present', 'absent' => 'Absent', 'late_validated' => 'Late', 'justified' => 'Justified'] as $value => $label)
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
                    <button class="sc-btn sc-btn-secondary mt-5">Save manual status</button>
                </form>
            </section>
        </section>

        <aside class="space-y-6">
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">QR / code attendance</h2>

                @if ($qrSession)
                    <div class="mt-5 rounded-lg bg-slate-50 p-4 text-center" x-data="qrAttendanceData(@js($students->map->only(['id', 'name'])->values()))">
                        <div class="flex flex-col items-center justify-center p-4">
                            <x-ui.icon name="qr" class="mb-2 h-16 w-16 text-slate-400" />
                            <p class="text-sm font-semibold text-slate-600">QR actif pendant la phase presence.</p>
                            <p class="mt-1 text-xs font-semibold text-amber-700">Temps restant: <span x-text="countdownLabel"></span></p>
                            <button @click="showModal = true" type="button" class="sc-btn sc-btn-primary mt-4 w-full">Afficher le QR Code</button>
                            <template x-if="attempts.length > 0">
                                <button @click="showConflictsModal = true" type="button" class="sc-btn mt-3 w-full border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100">
                                    Voir <span x-text="attempts.length"></span> conflit(s) d'appareil
                                </button>
                            </template>
                        </div>

                        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/90 p-6 backdrop-blur-sm" x-cloak style="display: none;" @keydown.escape.window="showModal = false">
                            <div class="relative flex h-full w-full max-w-6xl rounded-3xl bg-white shadow-2xl overflow-hidden" @click.outside="showModal = false">
                                
                                <button @click="showModal = false" type="button" class="absolute right-6 top-6 z-10 rounded-full bg-slate-100 p-3 text-slate-400 hover:bg-slate-200 hover:text-slate-600 transition">
                                    <span class="sr-only">Close</span>
                                    <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>

                                <div class="flex flex-1 flex-col items-center justify-center bg-campus-50/50 p-10">
                                    <h2 class="mb-4 text-3xl font-black text-slate-800 text-center">Scannez pour marquer votre presence</h2>
                                    <div class="mb-8 rounded-full border border-amber-200 bg-amber-50 px-6 py-2 text-lg font-bold text-amber-700 shadow-sm">
                                        Temps restant: <span x-text="countdownLabel"></span>
                                    </div>
                                    
                                    <div class="relative rounded-3xl border-8 border-white bg-white p-4 shadow-xl">
                                        <img class="h-[450px] w-[450px] object-contain" :src="qrDataUri" src="{{ $qrDataUri }}" alt="QR attendance code">
                                        <div class="absolute -right-3 -top-3 rounded-full bg-emerald-500 p-2 text-white shadow-lg animate-spin-slow">
                                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('attendance.qr.stop', $session) }}" class="mt-8 w-full max-w-[450px]">
                                        @csrf
                                        <button type="submit" class="sc-btn sc-btn-danger w-full py-4 text-xl font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all">Terminer l'appel</button>
                                    </form>
                                </div>

                                <div class="flex w-[400px] flex-col border-l border-slate-200 bg-white">
                                    <div class="border-b border-slate-200 bg-slate-50 p-6">
                                        <h3 class="text-2xl font-bold text-slate-800">En direct</h3>
                                        <p class="mt-1 text-sm text-slate-500">Liste des presents en temps reel</p>
                                        
                                        <div class="mt-4 flex items-center justify-between rounded-xl bg-campus-600 p-4 text-white shadow-md">
                                            <div class="text-sm font-semibold opacity-80">Total Presents</div>
                                            <div class="text-3xl font-black"><span x-text="presentStudents.length"></span> / <span x-text="students.length"></span></div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex-1 overflow-y-auto p-4 space-y-2 bg-slate-50/50">
                                        <template x-if="presentStudents.length === 0">
                                            <div class="flex h-full flex-col items-center justify-center text-center opacity-50">
                                                <svg class="mb-4 size-16 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                                <p class="text-lg font-bold text-slate-600">En attente de scans...</p>
                                            </div>
                                        </template>

                                        <template x-for="student in presentStudents" :key="student.id">
                                            <div class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-white p-3 shadow-sm transition-all duration-300">
                                                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-600">
                                                    <span x-text="student.name.charAt(0)"></span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div class="truncate font-bold text-slate-800 text-sm" x-text="student.name"></div>
                                                    <div class="text-[10px] font-semibold text-emerald-600 flex items-center gap-1 uppercase tracking-wide">
                                                        <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                                        Present
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div x-show="showConflictsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4 backdrop-blur-sm" x-cloak style="display: none;" @keydown.escape.window="showConflictsModal = false">
                            <div class="relative w-full max-w-lg rounded-2xl bg-white p-8 text-left shadow-2xl" @click.outside="showConflictsModal = false">
                                <h2 class="text-xl font-bold text-slate-800">Conflits d'appareils</h2>
                                <p class="mt-2 text-sm text-slate-600">Validez leur presence uniquement apres verification.</p>
                                <div class="mt-5 max-h-96 space-y-3 overflow-y-auto pr-2">
                                    <template x-for="attempt in attempts" :key="attempt.id">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                            <span class="font-bold text-slate-800" x-text="attempt.stagiaire.name"></span>
                                        </div>
                                    </template>
                                </div>
                                <div class="mt-6 flex justify-end">
                                    <button type="button" class="sc-btn sc-btn-secondary" @click="showConflictsModal = false">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif ($qrClosed)
                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">
                        QR phase closed. Les stagiaires doivent utiliser la declaration de retard si la fenetre est encore ouverte.
                    </div>
                @else
                    <form method="POST" action="{{ route('attendance.qr.generate', $session) }}" class="mt-4">
                        @csrf
                        <button class="sc-btn sc-btn-primary w-full">Start Attendance</button>
                    </form>
                    <p class="mt-4 text-sm text-slate-500">Generate a dynamic QR code for stagiaires to check in. The code changes every 2 seconds to prevent cheating.</p>
                @endif
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Correction erreur QR</h2>
                <form method="POST" action="{{ route('attendance.correction.store', $session) }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-slate-200 p-3">
                        @foreach ($students as $student)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="rounded border-slate-300 text-primary">
                                <span>{{ $student->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <select name="correction_type" class="sc-input">
                        <option value="present">Mark as present</option>
                        <option value="late_validated">Mark as late validated</option>
                    </select>
                    <textarea name="reason" class="sc-input min-h-24" placeholder="Reason required: QR ferme par erreur, probleme projecteur, probleme connexion..."></textarea>
                    <button class="sc-btn sc-btn-secondary w-full">Save correction</button>
                </form>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Cloture</h2>
                <p class="mt-2 text-sm text-slate-500">La cloture est bloquee tant que des retards normaux sont en attente.</p>
                <form method="POST" action="{{ route('attendance.finalize', $session) }}" class="mt-4">
                    @csrf
                    <button class="sc-btn sc-btn-primary w-full">Valider la seance</button>
                </form>
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Correction history</h2>
                <div class="mt-4 space-y-3">
                    @forelse ($auditLogs as $log)
                        <div class="rounded-lg border border-slate-200 p-3">
                            <div class="text-sm font-semibold">{{ $log->stagiaire->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $log->old_status ?? 'none' }} -> {{ $log->new_status }} | {{ $log->changedBy->name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $log->reason }}</div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No correction history yet.</p>
                    @endforelse
                </div>
            </section>
        </aside>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('qrAttendanceData', (initialStudents = []) => ({
                    showModal: false,
                    showConflictsModal: false,
                    qrDataUri: '{{ $qrDataUri ?? '' }}',
                    interval: null,
                    attempts: [],
                    students: initialStudents.map(s => ({ ...s, status: 'absent' })),
                    secondsRemaining: {{ $attendanceSession ? (int) max(0, now()->diffInSeconds($attendanceSession->qrClosesAt(), false)) : 0 }},
                    
                    get countdownLabel() {
                        const minutes = Math.floor(this.secondsRemaining / 60).toString().padStart(2, '0');
                        const seconds = Math.max(0, this.secondsRemaining % 60).toString().padStart(2, '0');
                        return `${minutes}:${seconds}`;
                    },

                    get presentStudents() {
                        return this.students.filter(s => s.status === 'present' || s.status === 'late_validated');
                    },

                    init() {
                        if (window.Echo) {
                            window.Echo.private(`attendance.session.{{ $session->id }}`)
                                .listen('.App\\Events\\AttendanceMarked', (e) => {
                                    const student = this.students.find(s => s.id === e.studentId);
                                    if (student) {
                                        student.status = e.status;
                                        const radio = document.querySelector('input[name="attendance[' + e.studentId + ']"][value="' + e.status + '"]');
                                        if (radio) radio.checked = true;
                                    }
                                });
                        }

                        @if ($qrSession)
                        this.interval = setInterval(() => {
                            this.secondsRemaining = Math.max(0, this.secondsRemaining - 2);

                            fetch('{{ route('attendance.qr.refresh', $session) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                }
                            })
                            .then(res => {
                                if (res.status === 410) {
                                    clearInterval(this.interval);
                                    window.location.reload();
                                }
                                return res.json();
                            })
                            .then(data => {
                                if (data.qrDataUri) {
                                    this.qrDataUri = data.qrDataUri;
                                }
                                if (typeof data.secondsRemaining !== 'undefined') {
                                    this.secondsRemaining = Math.max(0, Math.floor(data.secondsRemaining));
                                }
                                if (data.attendances) {
                                    data.attendances.forEach(att => {
                                        const student = this.students.find(s => s.id === att.stagiaire_id);
                                        if (student) student.status = att.status;

                                        const radio = document.querySelector('input[name="attendance[' + att.stagiaire_id + ']"][value="' + att.status + '"]');
                                        if (radio) {
                                            radio.checked = true;
                                        }
                                    });
                                }
                                if (data.attempts) {
                                    this.attempts = data.attempts;
                                }
                            });
                        }, 2000);
                        @endif
                    }
                }));
            });
        </script>
    @endpush
</x-layouts.app>
