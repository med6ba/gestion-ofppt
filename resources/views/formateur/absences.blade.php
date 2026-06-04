<x-layouts.app :title="__('messages.absences.formateur_title')">
    @php
        $tone = fn (string $status) => match ($status) {
            'approved' => 'bg-emerald-100 text-emerald-700',
            'rejected' => 'bg-rose-100 text-rose-700',
            default => 'bg-amber-100 text-amber-700',
        };

        $sessionDateTime = fn ($session) => $session->starts_on
            ->copy()
            ->addDays($session->day_of_week - 1)
            ->setTimeFromTimeString(substr($session->starts_at, 0, 5));
    @endphp

    <div x-data="formateurAbsences()" class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">{{ __('messages.absences.formateur_title') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">{{ __('messages.absences.formateur_subtitle') }}</p>
            </div>
            <span class="sc-badge bg-emerald-100 text-emerald-700">{{ __('messages.nav.services') }}</span>
        </header>

        <div class="grid gap-6 xl:grid-cols-[440px_1fr]">
            <aside class="sc-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black">{{ __('messages.absences.available_sessions') }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ __('messages.absences.choose_session_hint') }}</p>
                    </div>
                    <x-ui.icon name="calendar" size="size-5 text-emerald-600" />
                </div>

                <div class="mt-4 grid gap-3">
                    @forelse ($upcomingSessions as $session)
                        @php
                            $startsAt = $sessionDateTime($session);
                            $canCancel = now()->diffInMinutes($startsAt, false) >= 120;
                            $hasPendingRequest = $pendingSessionIds->contains($session->id);
                            $sessionLabel = ($session->module->name ?? __('messages.absences.session'))
                                .' | '.($session->group->code ?? '')
                                .' | '.$startsAt->format('d/m/Y')
                                .' | '.$session->timeLabel();
                        @endphp

                        <article class="rounded-lg border border-slate-200 p-4">
                            <div class="flex items-start gap-3">
                                <div class="grid size-10 shrink-0 place-items-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <x-ui.icon name="clock" size="size-5" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="font-black text-slate-800">{{ $session->module->name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ $weekDays[$session->day_of_week] ?? '' }} {{ $startsAt->format('d/m/Y') }} | {{ $session->timeLabel() }}
                                    </div>
                                    <div class="mt-1 text-xs font-bold text-slate-400">
                                        {{ $session->group->code }} | {{ $session->room->code }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2">
                                @if ($hasPendingRequest)
                                    <span class="sc-badge bg-amber-100 text-amber-700">{{ __('messages.absences.already_pending') }}</span>
                                @elseif (! $canCancel)
                                    <span class="sc-badge bg-rose-100 text-rose-700">{{ __('messages.absences.too_late') }}</span>
                                @else
                                    <button
                                        type="button"
                                        class="sc-btn sc-btn-danger text-xs"
                                        @click="openCancelModal({{ $session->id }}, @js($sessionLabel))"
                                    >
                                        {{ __('messages.absences.request_cancellation') }}
                                    </button>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                            {{ __('messages.absences.no_formateur_sessions') }}
                        </p>
                    @endforelse
                </div>
            </aside>

            <section class="sc-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-black">{{ __('messages.absences.my_requests') }}</h2>
                    <span class="sc-badge bg-slate-100 text-slate-600">{{ __('messages.absences.requests_count', ['count' => $requests->total()]) }}</span>
                </div>

                <div class="mt-4 grid gap-3">
                    @forelse ($requests as $requestItem)
                        @php
                            $session = $requestItem->timetableSession;
                            $startsAt = $session ? $sessionDateTime($session) : null;
                        @endphp

                        <article class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">
                                        @if ($session)
                                            {{ $session->module->name }} | {{ $session->group->code }} | {{ $startsAt->format('d/m/Y') }} | {{ $session->timeLabel() }}
                                        @else
                                            {{ __('messages.absences.session') }}
                                        @endif
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">{{ $requestItem->reason }}</div>
                                    @if ($requestItem->review_note)
                                        <div class="mt-2 text-sm text-slate-500">{{ __('messages.common.note') }}: {{ $requestItem->review_note }}</div>
                                    @endif
                                    @if ($requestItem->reviewedBy && $requestItem->reviewed_at)
                                        <div class="mt-2 text-xs text-slate-400">
                                            {{ __('messages.absences.processed_by', ['name' => $requestItem->reviewedBy->name, 'date' => $requestItem->reviewed_at->format('d/m/Y')]) }}
                                        </div>
                                    @endif
                                </div>
                                <span class="sc-badge {{ $tone($requestItem->status) }}">{{ __('messages.status.'.$requestItem->status) }}</span>
                            </div>
                        </article>
                    @empty
                        <p class="text-sm text-slate-500">{{ __('messages.absences.empty') }}</p>
                    @endforelse
                </div>

                <div class="mt-4">{{ $requests->links() }}</div>
            </section>
        </div>

        <template x-teleport="body">
            <div x-show="showCancelModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showCancelModal = false">
                <div class="sc-modal" @click.outside="showCancelModal = false">
                    <div class="sc-modal-header">
                        <h3 class="text-lg font-bold text-rose-600">{{ __('messages.absences.request_cancellation') }}</h3>
                        <button @click="showCancelModal = false" class="sc-modal-close" type="button">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
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
                                <label class="sc-label">{{ __('messages.absences.cancellation_reason') }}</label>
                                <textarea
                                    x-model="cancelReason"
                                    class="sc-input mt-1"
                                    rows="4"
                                    required
                                    minlength="10"
                                    placeholder="{{ __('messages.absences.cancellation_placeholder') }}"
                                ></textarea>
                            </div>
                        </div>
                        <div class="sc-modal-footer">
                            <button @click="showCancelModal = false" type="button" class="sc-btn sc-btn-secondary">{{ __('messages.common.cancel') }}</button>
                            <button type="submit" class="sc-btn sc-btn-danger" :disabled="submitting">{{ __('messages.absences.send_cancellation') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('formateurAbsences', () => ({
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
                            const res = await axios.post(`/formateur/timetable/sessions/${this.cancelSessionId}/cancel-request`, 
                                { reason: this.cancelReason },
                                { headers: { 'Accept': 'application/json' } }
                            );
                            const data = res.data;

                            if (data.success) {
                                window.location.reload();
                                return;
                            }
                        } catch (err) {
                            const response = err.response;
                            if (response && response.status === 422) {
                                this.formErrors = Object.values(response.data.errors || {}).flat();
                            } else {
                                this.formErrors = [(response && response.data && response.data.message) || 'Une erreur est survenue'];
                            }
                        } finally {
                            this.submitting = false;
                        }
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
