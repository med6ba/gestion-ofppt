<x-layouts.app title="Emploi du Temps">
@php
    $canManage = auth()->user()->isSurveillant();
    $statusClass = match($weeklyTimetable?->status ?? '') {
        'draft' => 'sc-status-draft',
        'published' => 'sc-status-published',
        default => '',
    };
    $statusLabel = match($weeklyTimetable?->status ?? '') {
        'draft' => 'Brouillon',
        'published' => 'Publié',
        default => '',
    };
@endphp

<div x-data="timetableManager()" class="space-y-6">
    {{-- ─── Header Bar ─── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <h1 class="text-2xl font-black text-slate-800">Emploi du Temps</h1>
            @if ($weeklyTimetable)
                <span class="{{ $statusClass }} ml-4">{{ $statusLabel }}</span>
            @endif
        </div>

        @if ($canManage)
            <div class="flex flex-wrap gap-2 items-center">
                <button @click="showCreateTimetableModal = true" class="sc-btn sc-btn-secondary border-dashed border-2">
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                    Créer emploi
                </button>

                @if ($weeklyTimetable)
                    <button @click="showAddSessionModal = true" class="sc-btn sc-btn-primary">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Ajouter séance
                    </button>
                    @if ($weeklyTimetable->isDraft())
                        <form method="POST" action="{{ route('timetable.weekly.launch', $weeklyTimetable) }}">
                            @csrf
                            <button class="sc-btn sc-btn-primary" onclick="return confirm('Lancer cet emploi du temps ? Les notifications seront envoyées.')">
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Lancer l'emploi
                            </button>
                        </form>
                    @endif
                    <button @click="showDuplicateModal = true" class="sc-btn sc-btn-secondary">
                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                        Dupliquer
                    </button>
                @endif
            </div>
        @endif
    </div>

    {{-- ─── Weekly Grid ─── --}}
    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel ?? '',
        'selectedWeekStart' => $selectedWeekStart,
        'selectedWeekEnd' => $selectedWeekEnd,
        'isSelectedWeekActive' => $weeklyTimetable?->isPublished() ?? false,
        'showActions' => $canManage,
        'days' => $weekDays,
        'weekHistory' => $weekHistory,
        'groups' => $groups,
        'selectedGroupId' => $selectedGroupId,
    ])

    {{-- ─── Session List (below grid) ─── --}}
    @if ($gridSessions->isNotEmpty())
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Séances de la semaine</h2>
            <p class="mt-1 text-sm text-slate-500">{{ $gridSessions->count() }} séance(s) — {{ $gridSessions->where('status', 'cancelled')->count() }} annulée(s)</p>
            <div class="mt-4 grid gap-3">
                @foreach ($gridSessions as $session)
                    <div class="rounded-lg border border-slate-200 p-4 {{ $session->isCancelled() ? 'opacity-50 border-rose-200 bg-rose-50/30' : '' }}">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-semibold">
                                    {{ $weekDays[$session->day_of_week] ?? '' }} {{ $session->timeLabel() }}
                                    @if ($session->isCancelled())
                                        <span class="ml-2 rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-600">Annulée</span>
                                    @elseif ($session->status === 'changed')
                                        <span class="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-600">Modifiée</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} | {{ $session->module->name }} | {{ $session->room->code }} | {{ $session->formateur->name }}</div>
                                @if ($session->cancellation_reason)
                                    <div class="mt-1 text-xs text-rose-500">Raison: {{ $session->cancellation_reason }}</div>
                                @endif
                            </div>
                            @if ($canManage && !$session->isCancelled())
                                <div class="flex gap-2">
                                    <button @click="openEditSession({{ $session->id }}, @js($session->only(['module_id','formateur_id','room_id','day_of_week','starts_at','ends_at','change_note'])))" class="sc-btn sc-btn-secondary text-xs">Modifier</button>
                                    <button @click="openDeleteSession({{ $session->id }}, @js($session->module->name . ' — ' . ($weekDays[$session->day_of_week] ?? '') . ' ' . $session->timeLabel()))" class="sc-btn sc-btn-danger text-xs">Supprimer</button>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ─── Cancellation Requests (Surveillant only) ─── --}}
    @if ($canManage && $cancellationRequests->isNotEmpty())
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold text-rose-600">Demandes d'annulation en attente</h2>
            <div class="mt-4 grid gap-3">
                @foreach ($cancellationRequests as $req)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $req->formateur->name }}</div>
                                <div class="mt-1 text-sm text-slate-600">
                                    {{ $req->timetableSession->module->name }} — {{ $req->timetableSession->group->code }}
                                    — {{ $req->timetableSession->room->code }}
                                    — {{ $weekDays[$req->timetableSession->day_of_week] ?? '' }} {{ $req->timetableSession->timeLabel() }}
                                </div>
                                <div class="mt-2 rounded-lg bg-white p-2 text-sm text-slate-700">{{ $req->reason }}</div>
                                <div class="mt-1 text-xs text-slate-400">Demandé {{ $req->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <form method="POST" action="{{ route('timetable.cancellations.approve', $req) }}">
                                    @csrf
                                    <input type="text" name="review_note" placeholder="Note (optionnel)" class="sc-input mb-2 text-xs">
                                    <button class="sc-btn sc-btn-primary w-full text-xs">Approuver</button>
                                </form>
                                <form method="POST" action="{{ route('timetable.cancellations.reject', $req) }}">
                                    @csrf
                                    <input type="hidden" name="review_note" value="">
                                    <button class="sc-btn sc-btn-danger w-full text-xs">Rejeter</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════════════════════════════════════ --}}
    {{-- MODALS --}}
    {{-- ═══════════════════════════════════════════ --}}

    {{-- Modal: Create Weekly Timetable --}}
    <template x-teleport="body">
        <div x-show="showCreateTimetableModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showCreateTimetableModal = false">
            <div class="sc-modal" @click.outside="showCreateTimetableModal = false">
                <div class="sc-modal-header">
                    <h3 class="text-lg font-bold">Créer emploi du temps</h3>
                    <button @click="showCreateTimetableModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitCreateTimetable">
                    <div class="sc-modal-body space-y-4">
                        <template x-if="formErrors.length > 0">
                            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                <template x-for="err in formErrors" :key="err"><div x-text="err"></div></template>
                            </div>
                        </template>
                        <div>
                            <label class="sc-label">Groupe</label>
                            <x-ui.select
                                x-model="createForm.group_id"
                                :alpine-options="Js::from($groups->map(fn($g) => ['id' => $g->id, 'name' => $g->code . ' - ' . $g->name]))"
                            />
                        </div>
                        <div>
                            <label class="sc-label">Semaine (Lundi)</label>
                            <input x-model="createForm.week_start_date" type="date" class="sc-input mt-1" required>
                            <p class="mt-2 text-sm text-slate-500">Choisissez la semaine que vous souhaitez préparer. Vous pourrez ensuite ajouter les séances pour chaque classe.</p>
                        </div>
                        <div>
                            <label class="sc-label">Titre (optionnel)</label>
                            <input x-model="createForm.title" type="text" class="sc-input mt-1" placeholder="Ex: Planning principal">
                        </div>
                        <div>
                            <label class="sc-label">Notes (optionnel)</label>
                            <textarea x-model="createForm.notes" class="sc-input mt-1" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="sc-modal-footer">
                        <button @click="showCreateTimetableModal = false" type="button" class="sc-btn sc-btn-secondary">Annuler</button>
                        <button type="submit" class="sc-btn sc-btn-primary" :disabled="submitting">Créer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- Modal: Duplicate Timetable --}}
    @if ($weeklyTimetable)
    <template x-teleport="body">
        <div x-show="showDuplicateModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showDuplicateModal = false">
            <div class="sc-modal" @click.outside="showDuplicateModal = false">
                <div class="sc-modal-header">
                    <h3 class="text-lg font-bold">Dupliquer l'emploi du temps</h3>
                    <button @click="showDuplicateModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form method="POST" action="{{ route('timetable.weekly.duplicate', $weeklyTimetable) }}">
                    @csrf
                    <div class="sc-modal-body space-y-4">
                        <p class="text-sm text-slate-600">Copier cet emploi du temps (S{{ $weeklyTimetable->week_start_date->weekOfYear }}) vers une nouvelle semaine :</p>
                        <div>
                            <label class="sc-label">Début de la nouvelle semaine (Lundi)</label>
                            <input type="date" name="new_week_start" value="{{ $weeklyTimetable->week_start_date->copy()->addWeek()->toDateString() }}" class="sc-input mt-1" required>
                        </div>
                    </div>
                    <div class="sc-modal-footer">
                        <button @click="showDuplicateModal = false" type="button" class="sc-btn sc-btn-secondary">Annuler</button>
                        <button type="submit" class="sc-btn sc-btn-primary">Dupliquer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
    @endif

    {{-- Modal: Add Session --}}
    <template x-teleport="body">
        <div x-show="showAddSessionModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showAddSessionModal = false">
            <div class="sc-modal sc-modal-lg" @click.outside="showAddSessionModal = false">
                <div class="sc-modal-header">
                    <h3 class="text-lg font-bold">Ajouter une séance</h3>
                    <button @click="showAddSessionModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitAddSession">
                    <div class="sc-modal-body space-y-4">
                        <template x-if="formErrors.length > 0">
                            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                <template x-for="err in formErrors" :key="err"><div x-text="err"></div></template>
                            </div>
                        </template>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="sc-label">Groupe</label>
                                <x-ui.select
                                    x-model="sessionForm.group_id"
                                    :alpine-options="Js::from($groups->map(fn($g) => ['id' => $g->id, 'name' => $g->code . ' - ' . $g->name]))"
                                />
                            </div>
                            <div>
                                <label class="sc-label">Semaine (Lundi)</label>
                                <input x-model="sessionForm.week_start_date" type="date" class="sc-input mt-1" required>
                            </div>
                            <div>
                                <label class="sc-label">Module</label>
                                <select x-model="sessionForm.module_id" class="sc-input mt-1" required>
                                    <option value="">— Choisir —</option>
                                    <template x-for="module in filteredModules" :key="module.id">
                                        <option :value="module.id" x-text="module.code + ' - ' + module.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="sc-label">Formateur</label>
                                <select x-model="sessionForm.formateur_id" class="sc-input mt-1" required>
                                    <option value="">— Choisir —</option>
                                    <template x-for="formateur in filteredFormateurs" :key="formateur.id">
                                        <option :value="formateur.id" x-text="formateur.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="sc-label">Salle</label>
                                <select x-model="sessionForm.room_id" class="sc-input mt-1" required>
                                    <option value="">— Choisir —</option>
                                    @foreach ($rooms as $room)
                                        <option value="{{ $room->id }}">{{ $room->code }} - {{ $room->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="sc-label">Jour</label>
                                <select x-model="sessionForm.day_of_week" class="sc-input mt-1" required>
                                    @foreach ($weekDays as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-2">
                                <label class="sc-label">Horaire (OFPPT - 2h30)</label>
                                <select x-model="sessionForm.time_slot" @change="sessionForm.starts_at = $event.target.value.split('-')[0]; sessionForm.ends_at = $event.target.value.split('-')[1];" class="sc-input mt-1" required>
                                    @foreach ($sessionSlots as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="hidden" x-model="sessionForm.starts_at">
                                <input type="hidden" x-model="sessionForm.ends_at">
                            </div>
                        </div>
                        <div>
                            <label class="sc-label">Note (optionnel)</label>
                            <textarea x-model="sessionForm.change_note" class="sc-input mt-1" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="sc-modal-footer">
                        <button @click="showAddSessionModal = false" type="button" class="sc-btn sc-btn-secondary">Annuler</button>
                        <button type="submit" class="sc-btn sc-btn-primary" :disabled="submitting">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- Modal: Edit Session --}}
    <template x-teleport="body">
        <div x-show="showEditSessionModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showEditSessionModal = false">
            <div class="sc-modal sc-modal-lg" @click.outside="showEditSessionModal = false">
                <div class="sc-modal-header">
                    <h3 class="text-lg font-bold">Modifier la séance</h3>
                    <button @click="showEditSessionModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitEditSession">
                    <div class="sc-modal-body space-y-4">
                        <template x-if="formErrors.length > 0">
                            <div class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                <template x-for="err in formErrors" :key="err"><div x-text="err"></div></template>
                            </div>
                        </template>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="sc-label">Module</label>
                                <x-ui.select
                                    x-model="editForm.module_id"
                                    alpine-options="filteredEditModules"
                                    label-expr="opt.code + ' - ' + opt.name"
                                />
                            </div>
                            <div>
                                <label class="sc-label">Formateur</label>
                                <x-ui.select
                                    x-model="editForm.formateur_id"
                                    alpine-options="filteredEditFormateurs"
                                />
                            </div>
                            <div>
                                <label class="sc-label">Salle</label>
                                <x-ui.select
                                    x-model="editForm.room_id"
                                    :alpine-options="Js::from($rooms->map(fn($r) => ['id' => $r->id, 'name' => $r->code . ' - ' . $r->name]))"
                                />
                            </div>
                            <div>
                                <label class="sc-label">Jour</label>
                                <x-ui.select
                                    x-model="editForm.day_of_week"
                                    :alpine-options="Js::from(collect($weekDays)->map(fn($l, $v) => ['id' => $v, 'name' => $l])->values())"
                                />
                            </div>
                            <div class="col-span-2 sm:col-span-2">
                                <label class="sc-label">Horaire (OFPPT - 2h30)</label>
                                <x-ui.select
                                    x-model="editForm.time_slot"
                                    :alpine-options="Js::from(collect($sessionSlots)->map(fn($label, $value) => ['id' => $value, 'name' => $label])->values())"
                                    placeholder="— Choisir l'horaire —"
                                />
                                <input type="hidden" x-model="editForm.starts_at">
                                <input type="hidden" x-model="editForm.ends_at">
                            </div>
                        </div>
                        <div>
                            <label class="sc-label">Note de modification</label>
                            <textarea x-model="editForm.change_note" class="sc-input mt-1" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="sc-modal-footer">
                        <button @click="showEditSessionModal = false" type="button" class="sc-btn sc-btn-secondary">Annuler</button>
                        <button type="submit" class="sc-btn sc-btn-primary" :disabled="submitting">Sauvegarder</button>
                    </div>
                </form>
            </div>
        </div>
    </template>

    {{-- Modal: Delete Session --}}
    <template x-teleport="body">
        <div x-show="showDeleteSessionModal" x-cloak class="sc-modal-backdrop" @keydown.escape.window="showDeleteSessionModal = false">
            <div class="sc-modal" @click.outside="showDeleteSessionModal = false">
                <div class="sc-modal-header">
                    <h3 class="text-lg font-bold text-rose-600">Supprimer la séance</h3>
                    <button @click="showDeleteSessionModal = false" class="sc-modal-close"><svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                </div>
                <form @submit.prevent="submitDeleteSession">
                    <div class="sc-modal-body space-y-4">
                        <div class="rounded-lg border border-rose-200 bg-rose-50 p-4">
                            <p class="font-semibold text-rose-700">Êtes-vous sûr de vouloir supprimer cette séance ?</p>
                            <p class="mt-1 text-sm text-rose-600" x-text="deleteSessionLabel"></p>
                        </div>
                        <div>
                            <label class="sc-label">Raison (optionnel)</label>
                            <textarea x-model="deleteReason" class="sc-input mt-1" rows="2" placeholder="Expliquez pourquoi..."></textarea>
                        </div>
                    </div>
                    <div class="sc-modal-footer">
                        <button @click="showDeleteSessionModal = false" type="button" class="sc-btn sc-btn-secondary">Annuler</button>
                        <button type="submit" class="sc-btn sc-btn-danger" :disabled="submitting">Supprimer</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('timetableManager', () => ({
        init() {
            this.$watch('sessionForm.time_slot', value => {
                if(value && value.includes('-')) {
                    this.sessionForm.starts_at = value.split('-')[0];
                    this.sessionForm.ends_at = value.split('-')[1];
                }
            });
            this.$watch('editForm.time_slot', value => {
                if(value && value.includes('-')) {
                    this.editForm.starts_at = value.split('-')[0];
                    this.editForm.ends_at = value.split('-')[1];
                }
            });
        },
        showCreateTimetableModal: false,
        showDuplicateModal: false,
        showAddSessionModal: false,
        showEditSessionModal: false,
        showDeleteSessionModal: false,
        submitting: false,
        formErrors: [],

        allModules: @js($modules),
        allFormateurs: @js($formateurs),
        formateurModules: @js($formateur_modules ?? []),

        get filteredModules() {
            let fId = this.sessionForm.formateur_id;
            if (!fId) return this.allModules;
            let moduleIds = this.formateurModules.filter(fm => fm.formateur_id == fId).map(fm => fm.module_id);
            return this.allModules.filter(m => moduleIds.includes(m.id));
        },

        get filteredFormateurs() {
            let mId = this.sessionForm.module_id;
            if (!mId) return this.allFormateurs;
            let formateurIds = this.formateurModules.filter(fm => fm.module_id == mId).map(fm => fm.formateur_id);
            return this.allFormateurs.filter(f => formateurIds.includes(f.id));
        },

        get filteredEditModules() {
            let fId = this.editForm.formateur_id;
            if (!fId) return this.allModules;
            let moduleIds = this.formateurModules.filter(fm => fm.formateur_id == fId).map(fm => fm.module_id);
            return this.allModules.filter(m => moduleIds.includes(m.id));
        },

        get filteredEditFormateurs() {
            let mId = this.editForm.module_id;
            if (!mId) return this.allFormateurs;
            let formateurIds = this.formateurModules.filter(fm => fm.module_id == mId).map(fm => fm.formateur_id);
            return this.allFormateurs.filter(f => formateurIds.includes(f.id));
        },

        editingSessionId: null,
        deleteSessionId: null,
        deleteSessionLabel: '',
        deleteReason: '',

        createForm: {
            group_id: '{{ $selectedGroupId }}',
            week_start_date: '{{ now()->next('Monday')->toDateString() }}',
            title: '',
            notes: '',
        },

        sessionForm: {
            weekly_timetable_id: '{{ $weeklyTimetable?->id ?? '' }}',
            group_id: '{{ $selectedGroupId }}',
            week_start_date: '{{ $selectedWeekStart->toDateString() }}',
            module_id: '',
            formateur_id: '',
            room_id: '',
            day_of_week: '1',
            time_slot: '08:30-11:00',
            starts_at: '08:30',
            ends_at: '11:00',
            change_note: '',
        },

        editForm: {
            module_id: '',
            formateur_id: '',
            room_id: '',
            day_of_week: '',
            time_slot: '',
            starts_at: '',
            ends_at: '',
            change_note: '',
        },

        openEditSession(id, data) {
            const startsAt = data.starts_at ? data.starts_at.substring(0, 5) : '';
            const endsAt = data.ends_at ? data.ends_at.substring(0, 5) : '';
            this.editingSessionId = id;
            this.editForm = {
                module_id: String(data.module_id),
                formateur_id: String(data.formateur_id),
                room_id: String(data.room_id),
                day_of_week: String(data.day_of_week),
                time_slot: startsAt && endsAt ? (startsAt + '-' + endsAt) : '',
                starts_at: startsAt,
                ends_at: endsAt,
                change_note: data.change_note || '',
            };
            this.formErrors = [];
            this.showEditSessionModal = true;
        },

        openDeleteSession(id, label) {
            this.deleteSessionId = id;
            this.deleteSessionLabel = label;
            this.deleteReason = '';
            this.showDeleteSessionModal = true;
        },


        async submitCreateTimetable() {
            this.submitting = true;
            this.formErrors = [];
            try {
                const res = await fetch('{{ route("timetable.weekly.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.createForm),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.formErrors = data.errors ? (Array.isArray(data.errors) ? data.errors : Object.values(data.errors).flat()) : ['Erreur inconnue.'];
                }
            } catch (e) {
                this.formErrors = ['Erreur réseau.'];
            }
            this.submitting = false;
        },

        async submitAddSession() {
            this.submitting = true;
            this.formErrors = [];
            try {
                const res = await fetch('{{ route("timetable.sessions.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.sessionForm),
                });
                const data = await res.json();
                if (data.success) {
                    if (data.redirect) {
                        window.location.href = data.redirect;
                    } else {
                        window.location.reload();
                    }
                } else {
                    this.formErrors = data.errors ? (Array.isArray(data.errors) ? data.errors : Object.values(data.errors).flat()) : ['Erreur inconnue.'];
                }
            } catch (e) {
                this.formErrors = ['Erreur réseau.'];
            }
            this.submitting = false;
        },

        async submitEditSession() {
            this.submitting = true;
            this.formErrors = [];
            try {
                const res = await fetch(`/timetable/sessions/${this.editingSessionId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify(this.editForm),
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

        async submitDeleteSession() {
            this.submitting = true;
            try {
                const res = await fetch(`/timetable/sessions/${this.deleteSessionId}`, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ reason: this.deleteReason }),
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (e) {}
            this.submitting = false;
        },
    }));
});
</script>
@endpush
</x-layouts.app>
