<x-layouts.app title="Mon Emploi du Temps">
    <div x-data="myTimetable()" class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <h1 class="text-2xl font-bold">{{ $scheduleLabel }}</h1>
            
            <div class="flex items-center gap-2">
                @if ($weekHistory->isNotEmpty())
                    <form method="GET" action="{{ route('timetable.mine') }}">
                        <select name="week_start" class="sc-input !w-auto text-sm" onchange="this.form.submit()">
                            @foreach ($weekHistory as $wh)
                                <option value="{{ $wh->week_start_date->toDateString() }}" @selected($selectedWeekStart->isSameDay($wh->week_start_date))>
                                    S{{ $wh->week_start_date->weekOfYear }} — {{ $wh->week_start_date->format('d/m') }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>
        </div>

        @include('timetable.partials.grid', [
            'sessions' => $gridSessions,
            'scheduleLabel' => $scheduleLabel,
            'selectedWeekStart' => $selectedWeekStart,
            'selectedWeekEnd' => $selectedWeekEnd,
            'isSelectedWeekActive' => true,
            'showActions' => false,
            'days' => $weekDays,
        ])

        {{-- ─── Cancellation Request Form (Formateur only) ─── --}}
        @if ($isFormateur && $gridSessions->where('status', 'scheduled')->isNotEmpty())
            <section class="sc-card p-5">
                <h2 class="text-lg font-bold">Demander une annulation</h2>
                <p class="mt-1 text-sm text-slate-500">Vous pouvez demander l'annulation d'une séance au moins 2 heures avant son début.</p>
                <div class="mt-4 grid gap-3">
                    @foreach ($gridSessions->where('status', 'scheduled') as $session)
                        @php
                            $sessionDate = $session->starts_on->copy()->addDays($session->day_of_week - 1);
                            $sessionDateTime = $sessionDate->setTimeFromTimeString($session->starts_at);
                            $canCancel = now()->diffInMinutes($sessionDateTime, false) >= 120;
                        @endphp
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-slate-200 p-4">
                            <div>
                                <div class="font-semibold">{{ $weekDays[$session->day_of_week] ?? '' }} {{ $session->timeLabel() }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} | {{ $session->module->name }} | {{ $session->room->code }}</div>
                            </div>
                            @if ($canCancel)
                                <button @click="openCancelModal({{ $session->id }}, '{{ addslashes($session->module->name . ' — ' . ($weekDays[$session->day_of_week] ?? '') . ' ' . $session->timeLabel()) }}')" class="sc-btn sc-btn-danger text-xs">
                                    Demander annulation
                                </button>
                            @else
                                <span class="text-xs text-rose-500">Trop tard pour annuler</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Modal: Request Cancellation --}}
        <template x-teleport="body">
            <div x-show="showCancelModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showCancelModal = false">
                <div class="sc-modal" @click.outside="showCancelModal = false">
                    <div class="sc-modal-header">
                        <h3 class="text-lg font-bold text-rose-600">Demander l'annulation</h3>
                        <button @click="showCancelModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                    </div>
                    <form @submit.prevent="submitCancelRequest">
                        <div class="sc-modal-body space-y-4">
                            <template x-if="formErrors.length > 0">
                                <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                    <template x-for="err in formErrors" :key="err"><div x-text="err"></div></template>
                                </div>
                            </template>
                            <div class="rounded-lg bg-slate-50 p-3 text-sm font-semibold text-slate-700" x-text="cancelSessionLabel"></div>
                            <div>
                                <label class="sc-label">Raison de l'annulation (obligatoire)</label>
                                <textarea x-model="cancelReason" class="sc-input mt-1" rows="3" required minlength="10" placeholder="Veuillez détailler la raison..."></textarea>
                            </div>
                        </div>
                        <div class="sc-modal-footer">
                            <button @click="showCancelModal = false" type="button" class="sc-btn sc-btn-secondary">Retour</button>
                            <button type="submit" class="sc-btn sc-btn-danger" :disabled="submitting">Envoyer la demande</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('myTimetable', () => ({
            showCancelModal: false,
            submitting: false,
            formErrors: [],
            cancelSessionId: null,
            cancelSessionLabel: '',
            cancelReason: '',

            openCancelModal(id, label) {
                this.cancelSessionId = id;
                this.cancelSessionLabel = label;
                this.cancelReason = '';
                this.formErrors = [];
                this.showCancelModal = true;
            },

            async submitCancelRequest() {
                this.submitting = true;
                this.formErrors = [];
                try {
                    const res = await fetch(`/formateur/timetable/sessions/${this.cancelSessionId}/cancel-request`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        body: JSON.stringify({ reason: this.cancelReason }),
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.reload();
                    } else {
                        this.formErrors = data.errors ? (Array.isArray(data.errors) ? data.errors : Object.values(data.errors).flat()) : ['Erreur inconnue.'];
                    }
                } catch (e) {
                    this.formErrors = ['Erreur réseau.'];
                }
                this.submitting = false;
            },
        }));
    });
    </script>
    @endpush
</x-layouts.app>
