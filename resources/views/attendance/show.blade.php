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
                        @php $current = $attendanceByStudent->get($student->id)?->status ?? 'late'; @endphp
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
                <h2 class="text-lg font-bold">QR Attendance</h2>

                @if ($qrSession)
                    <div class="mt-5 rounded-lg bg-slate-50 p-4 text-center" x-data="qrAttendanceData()">
                        <div class="flex flex-col items-center justify-center p-4">
                            <x-ui.icon name="qr" class="h-16 w-16 text-slate-400 mb-2" />
                            <p class="text-sm font-semibold text-slate-600 mb-4">La session QR est active en plein écran.</p>
                            <button @click="showModal = true" type="button" class="sc-btn sc-btn-primary w-full">Afficher le QR Code</button>
                            <template x-if="attempts.length > 0">
                                <button @click="showConflictsModal = true" type="button" class="sc-btn mt-3 w-full border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100">
                                    Voir <span x-text="attempts.length"></span> conflit(s) d'appareil
                                </button>
                            </template>
                        </div>

                        <!-- Large Modal for QR Code -->
                        <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4 backdrop-blur-sm" x-cloak style="display: none;" @keydown.escape.window="showModal = false">
                            <div class="relative flex w-full max-w-3xl flex-col items-center justify-center rounded-2xl bg-white p-10 shadow-2xl" @click.outside="showModal = false">
                                <button @click="showModal = false" type="button" class="absolute right-4 top-4 rounded-full p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                                
                                <h2 class="mb-6 text-3xl font-bold text-slate-800">Scannez pour marquer votre présence</h2>
                                <img class="mx-auto h-[500px] w-[500px] rounded-xl border-4 border-slate-100 bg-white p-4" :src="qrDataUri" src="{{ $qrDataUri }}" alt="QR attendance code">
                                
                                <div class="mt-8 rounded-xl border border-emerald-200 bg-emerald-50 px-6 py-4 text-center text-lg font-semibold text-emerald-700 w-full max-w-[500px]">
                                    Uniquement via l'appareil enregistré
                                </div>

                                <form method="POST" action="{{ route('attendance.qr.stop', $session) }}" class="mt-6 w-full max-w-[500px]">
                                    @csrf
                                    <button type="submit" class="sc-btn sc-btn-danger w-full bg-rose-600 text-white hover:bg-rose-700 text-lg py-3">Stop Attendance</button>
                                </form>
                            </div>
                        </div>

                        <!-- Modal for Device Conflicts -->
                        <div x-show="showConflictsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 p-4 backdrop-blur-sm" x-cloak style="display: none;" @keydown.escape.window="showConflictsModal = false">
                            <div class="relative w-full max-w-lg rounded-2xl bg-white p-8 shadow-2xl text-left" @click.outside="showConflictsModal = false">
                                <div class="flex items-center gap-3 mb-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 text-amber-600">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    </div>
                                    <h2 class="text-xl font-bold text-slate-800">Conflits d'appareils</h2>
                                </div>
                                <p class="mb-6 text-sm text-slate-600">Ces étudiants ont utilisé le téléphone d'un autre élève pour scanner le QR code. Interrogez-les et validez leur présence.</p>
                                
                                <div class="space-y-3 max-h-96 overflow-y-auto pr-2">
                                    <template x-for="attempt in attempts" :key="attempt.id">
                                        <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4 bg-slate-50">
                                            <span class="font-bold text-slate-800" x-text="attempt.stagiaire.name"></span>
                                            <div class="flex gap-2">
                                                <button type="button" class="sc-btn sc-btn-sm sc-btn-secondary text-rose-600 hover:bg-rose-50" @click="resolveConflict(attempt, 'absent')">Absent</button>
                                                <button type="button" class="sc-btn sc-btn-sm border-emerald-300 bg-emerald-100 text-emerald-700 hover:bg-emerald-200" @click="resolveConflict(attempt, 'present')">Présent</button>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                
                                <div class="mt-6 flex justify-end">
                                    <button type="button" class="sc-btn sc-btn-secondary" @click="showConflictsModal = false">Fermer</button>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <form method="POST" action="{{ route('attendance.qr.generate', $session) }}" class="mt-4">
                        @csrf
                        <button class="sc-btn sc-btn-primary w-full">Start QR Attendance</button>
                    </form>
                    <p class="mt-4 text-sm text-slate-500">Generate a dynamic QR code for stagiaires to check in. The code changes every 5 seconds to prevent cheating.</p>
                @endif
            </section>

            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Règles de validation</h2>
                <div class="mt-3 space-y-2 text-sm text-slate-600">
                    <p>Compte stagiaire approuvé requis.</p>
                    <p>Appartenance au bon groupe requise.</p>
                    <p>Le token/QR doit être valide et non expiré.</p>
                    <p><strong>L'appareil est enregistré de manière unique (Fingerprint).</strong></p>
                </div>
            </section>
        </aside>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('qrAttendanceData', () => ({
                showModal: false,
                showConflictsModal: false,
                qrDataUri: '{{ $qrDataUri ?? '' }}',
                interval: null,
                attempts: [],
                init() {
                    // Watch for QR modal closing to show conflicts modal if needed
                    this.$watch('showModal', (value) => {
                        if (value === false && this.attempts.length > 0) {
                            this.showConflictsModal = true;
                        }
                    });

                    @if ($qrSession)
                    // Real-time WebSocket connection
                    if (window.Echo) {
                        window.Echo.private('attendance.session.{{ $session->id }}')
                            .listen('AttendanceMarked', (e) => {
                                const radio = document.querySelector('input[name="attendance[' + e.studentId + ']"][value="' + e.status + '"]');
                                if (radio) {
                                    radio.checked = true;
                                }
                            })
                            .listen('DeviceConflictDetected', (e) => {
                                // Check if attempt already exists
                                if (!this.attempts.find(a => a.id === e.attemptId)) {
                                    this.attempts.push({
                                        id: e.attemptId,
                                        stagiaire_id: e.student.id,
                                        stagiaire: e.student
                                    });
                                }
                            });
                    }

                    // Keep polling to rotate QR Code Token
                    this.interval = setInterval(() => {
                        fetch('{{ route('attendance.qr.refresh', $session) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.qrDataUri) {
                                this.qrDataUri = data.qrDataUri;
                            }
                            if (data.attendances) {
                                data.attendances.forEach(att => {
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
                    }, 5000);
                    @endif
                },
                resolveConflict(attempt, status) {
                    const radio = document.querySelector('input[name="attendance[' + attempt.stagiaire_id + ']"][value="' + status + '"]');
                    if (radio) {
                        radio.checked = true;
                    }
                    this.attempts = this.attempts.filter(a => a.id !== attempt.id);
                    if (this.attempts.length === 0) {
                        this.showConflictsModal = false;
                    }
                }
            }));
        });
    </script>
    @endpush
</x-layouts.app>
