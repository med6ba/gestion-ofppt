<x-layouts.app :title="__('messages.absences.title')">
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

    <div x-data="stagiaireAbsences()" class="space-y-6">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-800">{{ __('messages.absences.title') }}</h1>
                <p class="mt-1 max-w-2xl text-sm text-slate-500">Demandez une autorisation d'absence ou consultez l'historique de vos demandes.</p>
            </div>
            <span class="sc-badge bg-emerald-100 text-emerald-700">{{ __('messages.nav.services') }}</span>
        </header>

        <div class="grid gap-6 xl:grid-cols-[440px_1fr]">
            <aside class="sc-card p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black">{{ __('messages.absences.available_sessions') ?? 'Séances à venir' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Choisissez une séance pour demander une absence.</p>
                    </div>
                    <x-ui.icon name="calendar" size="size-5 text-emerald-600" />
                </div>

                <div class="mt-4 grid gap-3">
                    @forelse ($upcomingSessions as $session)
                        @php
                            $startsAt = $sessionDateTime($session);
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
                                <button
                                    type="button"
                                    class="sc-btn sc-btn-secondary text-xs"
                                    @click="openAbsenceModal(@js($startsAt->format('Y-m-d')), @js(substr($session->starts_at, 0, 5)), @js(substr($session->ends_at, 0, 5)), @js($sessionLabel))"
                                >
                                    Demander une absence
                                </button>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                            Aucune séance à venir n'est disponible pour le moment.
                        </p>
                    @endforelse
                </div>
                
                <div class="mt-6 border-t border-slate-100 pt-4">
                    <button type="button" @click="openCustomModal()" class="w-full sc-btn bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100">
                        Demander pour une autre date
                    </button>
                </div>
            </aside>

            <section class="sc-card p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-black">{{ __('messages.absences.my_requests') }}</h2>
                    <span class="sc-badge bg-slate-100 text-slate-600">{{ __('messages.absences.requests_count', ['count' => $requests->total()]) }}</span>
                </div>

                <div class="mt-4 grid gap-3">
                    @forelse ($requests as $requestItem)
                        <article class="rounded-lg border border-slate-200 p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-800">
                                        {{ $requestItem->absence_date->format('d/m/Y') }} | {{ substr($requestItem->starts_at, 0, 5) }} - {{ substr($requestItem->ends_at, 0, 5) }}
                                    </div>
                                    <div class="mt-2 text-sm text-slate-600">{{ $requestItem->reason }}</div>
                                    
                                    @if ($requestItem->attachment_path)
                                        <a href="{{ route('absences.attachment', $requestItem) }}" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100">
                                            <svg class="size-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Fichier joint
                                        </a>
                                    @endif

                                    @if ($requestItem->surveillant_note)
                                        <div class="mt-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                                            <span class="font-bold">{{ __('messages.common.note') }}:</span> {{ $requestItem->surveillant_note }}
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

        <!-- Request Modal -->
        <template x-teleport="body">
            <div x-show="showModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showModal = false">
                <div class="sc-modal" @click.outside="showModal = false">
                    <div class="sc-modal-header">
                        <h3 class="text-lg font-bold text-slate-800">Demande d'absence</h3>
                        <button @click="showModal = false" class="sc-modal-close" type="button">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('absences.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="sc-modal-body space-y-4">
                            <template x-if="sessionLabel">
                                <div class="rounded-lg bg-emerald-50 border border-emerald-100 p-3 text-sm font-medium text-emerald-800" x-text="sessionLabel"></div>
                            </template>

                            <template x-if="!sessionLabel">
                                <div class="space-y-4">
                                    <div>
                                        <label class="sc-label">{{ __('messages.absences.absence_date') }}</label>
                                        <input class="sc-input mt-1" type="date" name="absence_date" x-model="absenceDate" required>
                                    </div>
                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label class="sc-label">{{ __('messages.absences.start_time') }}</label>
                                            <input class="sc-input mt-1" type="time" name="starts_at" x-model="startsAt" required>
                                        </div>
                                        <div>
                                            <label class="sc-label">{{ __('messages.absences.end_time') }}</label>
                                            <input class="sc-input mt-1" type="time" name="ends_at" x-model="endsAt" required>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            
                            <template x-if="sessionLabel">
                                <div>
                                    <!-- Hidden inputs if session is selected -->
                                    <input type="hidden" name="absence_date" :value="absenceDate">
                                    <input type="hidden" name="starts_at" :value="startsAt">
                                    <input type="hidden" name="ends_at" :value="endsAt">
                                </div>
                            </template>

                            <div>
                                <label class="sc-label">{{ __('messages.absences.reason') }}</label>
                                <textarea name="reason" class="sc-input mt-1 min-h-[100px]" required minlength="8" placeholder="Ex: Rendez-vous médical..."></textarea>
                            </div>
                            
                            <div>
                                <label class="sc-label">Fichier de permission d'absence</label>
                                <div class="mt-1 flex justify-center rounded-lg border border-dashed border-slate-300 px-6 py-8 hover:bg-slate-50 transition-colors relative cursor-pointer">
                                    <div class="text-center">
                                        <svg class="mx-auto size-10 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                                        <div class="mt-4 flex text-sm leading-6 text-slate-600 justify-center">
                                            <label for="attachment" class="relative cursor-pointer rounded-md bg-transparent font-semibold text-emerald-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-emerald-600 focus-within:ring-offset-2 hover:text-emerald-500">
                                                <span>Télécharger un fichier</span>
                                                <input id="attachment" name="attachment" type="file" class="sr-only" accept=".pdf,.jpg,.jpeg,.png">
                                            </label>
                                            <p class="pl-1">ou glisser-déposer</p>
                                        </div>
                                        <p class="text-xs leading-5 text-slate-500">PDF, PNG, JPG jusqu'à 2MB</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="sc-modal-footer">
                            <button @click="showModal = false" type="button" class="sc-btn sc-btn-secondary">{{ __('messages.common.cancel') }}</button>
                            <button type="submit" class="sc-btn sc-btn-primary">{{ __('messages.absences.send_request') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('stagiaireAbsences', () => ({
                    showModal: false,
                    sessionLabel: '',
                    absenceDate: '',
                    startsAt: '',
                    endsAt: '',

                    openAbsenceModal(date, start, end, label) {
                        this.absenceDate = date;
                        this.startsAt = start;
                        this.endsAt = end;
                        this.sessionLabel = label;
                        this.showModal = true;
                    },
                    
                    openCustomModal() {
                        this.absenceDate = '';
                        this.startsAt = '';
                        this.endsAt = '';
                        this.sessionLabel = '';
                        this.showModal = true;
                    }
                }));
            });
        </script>
    @endpush
</x-layouts.app>
