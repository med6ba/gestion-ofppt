<x-layouts.app title="Saisie des Notes">
    @php
        $selectedGroupId = $selected['group']->id ?? null;
        $selectedModuleId = $selected['module']->id ?? null;
        $selectedPairValue = $selected ? "{$selectedGroupId}-{$selectedModuleId}" : null;
        $isPublished = $evaluations->contains(fn ($evaluation) => $evaluation->status === \App\Models\Evaluation::STATUS_PUBLISHED);
        $ccCount = $selected['module']->cc_count ?? 3;
        $efmMaxScore = (int) ($selected['module']->efm_max_score ?? 40);
        $formula = $selected['module']->grade_formula ?? 'moy_module = (moy_cc + efm) / 3';
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-800">Saisie des Notes</h2>
                <p class="mt-1 text-sm text-slate-500">Les notes du module se saisissent ensemble: CC /20 et EFM /{{ $efmMaxScore }}.</p>
            </div>
            @if ($selected)
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('evaluations.export.excel', ['group_id' => $selectedGroupId, 'module_id' => $selectedModuleId, 'formateur_id' => auth()->id()]) }}" class="sc-btn sc-btn-secondary">Télécharger Excel</a>
                    <a href="{{ route('evaluations.export.pdf', ['group_id' => $selectedGroupId, 'module_id' => $selectedModuleId, 'formateur_id' => auth()->id()]) }}" class="sc-btn sc-btn-primary">Télécharger PDF</a>
                </div>
            @endif
        </div>

        <form method="GET" class="mt-5 grid gap-3 md:grid-cols-[1fr_180px_auto]">
            <select name="pair" class="sc-input">
                @foreach ($pairs as $pair)
                    @php $pairValue = $pair['group']->id.'-'.$pair['module']->id; @endphp
                    <option value="{{ $pairValue }}" @selected($selectedPairValue === $pairValue)>{{ $pair['group']->code }} - {{ $pair['group']->name }} | {{ $pair['module']->name }}</option>
                @endforeach
            </select>
            <input type="date" name="evaluation_date" value="{{ $date }}" class="sc-input">
            <button class="sc-btn sc-btn-primary">Charger</button>
        </form>
    </section>

    @if (!$selected)
        <section class="mt-6 sc-card p-5">
            <p class="text-sm text-slate-500">Aucun groupe/module affecté à ce formateur.</p>
        </section>
    @else
        <form method="POST" action="{{ route('evaluations.grades.store') }}" class="mt-6"
              x-data="gradeForm({{ $expectedCount }})" x-init="init()">
            @csrf
            <input type="hidden" name="group_id" value="{{ $selectedGroupId }}">
            <input type="hidden" name="module_id" value="{{ $selectedModuleId }}">
            <input type="hidden" name="evaluation_date" value="{{ $date }}">

            <section class="sc-card p-5">
            @php
                $displayErrors = collect($errors->all())->filter(fn($m) => filled($m))->unique()->values();
            @endphp
            <!-- Server-side error banner - auto-hides when user modifies any field -->
            @if ($displayErrors->isNotEmpty())
                <div x-show="showErrors" x-cloak
                     class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    <div class="flex items-center gap-2 font-bold text-rose-800 mb-2">
                        <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                        Impossible d'enregistrer pour le moment.
                    </div>
                    <ul class="list-disc space-y-1 pl-5">
                        @foreach ($displayErrors as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Removed live warning since we no longer block on empty fields -->

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">{{ $selected['group']->code }} | {{ $selected['module']->name }}</h3>
                        <div class="mt-2 flex flex-wrap gap-2 text-xs font-bold uppercase">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-slate-600">{{ $ccCount }} CC /20</span>
                            <span class="rounded-full bg-campus-50 px-3 py-1 text-campus-800">EFM /{{ $efmMaxScore }}</span>
                            <span class="rounded-full bg-primary/10 px-3 py-1 text-primary">{{ $formula }}</span>
                        </div>
                        <p class="mt-2 text-sm font-medium">
                            <span :class="filledCount >= totalCount ? 'text-emerald-600' : 'text-slate-500'">
                                <span x-text="filledCount">{{ $enteredCount }}</span> / {{ $expectedCount }} notes saisies
                                <span x-show="filledCount >= totalCount" class="ml-1">✓</span>
                            </span>
                        </p>
                    </div>
                    <!-- Badge removed since everything is always published -->
                </div>

                @if ($isPublished)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <label class="text-xs font-bold uppercase text-amber-800">Motif obligatoire si vous corrigez une note publiée</label>
                        <input name="reason" value="{{ old('reason') }}" class="sc-input mt-2 bg-white" placeholder="Ex: erreur de saisie vérifiée">
                        @error('reason')
                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div class="mt-5 hidden overflow-x-auto md:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-3 py-3">CEF</th>
                                <th class="px-3 py-3">Nom & Prénom</th>
                                @foreach ($activeTypes as $noteType)
                                    <th class="min-w-44 px-3 py-3">{{ $typeLabels[$noteType] }} /{{ \App\Models\Evaluation::maxScoreFor($noteType) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($students as $student)
                                @php
                                    $rowAbsentState = collect($activeTypes)
                                        ->mapWithKeys(fn ($noteType) => [
                                            $noteType => (bool) old("grades.{$student->id}.{$noteType}.absent", $grades->get($noteType)?->get($student->id)?->absent ?? false),
                                        ])
                                        ->all();
                                @endphp
                                <tr x-data="{ absent: @js($rowAbsentState) }">
                                    <td class="px-3 py-3 font-mono text-xs">{{ $student->registration_number ?? '-' }}</td>
                                    <td class="px-3 py-3 font-semibold text-slate-800">{{ $student->name }}</td>
                                    @foreach ($activeTypes as $noteType)
                                        @php
                                            $grade = $grades->get($noteType)?->get($student->id);
                                            $maxScore = \App\Models\Evaluation::maxScoreFor($noteType);
                                            $isEfm = $noteType === \App\Models\Evaluation::TYPE_EFM;
                                            $scoreField = "grades.{$student->id}.{$noteType}.score";
                                            $absentField = "grades.{$student->id}.{$noteType}.absent";
                                            $observationField = "grades.{$student->id}.{$noteType}.observation";
                                        @endphp
                                        <td class="px-3 py-3 align-top">
                                            <input
                                                type="text"
                                                inputmode="decimal"
                                                name="grades[{{ $student->id }}][{{ $noteType }}][score]"
                                                value="{{ old($scoreField, $grade?->score) }}"
                                                data-grade-score
                                                data-empty="{{ old($scoreField, $grade?->score) === null || old($scoreField, $grade?->score) === '' ? 'true' : 'false' }}"
                                                class="sc-input w-32 {{ $errors->has($scoreField) ? 'ring-2 ring-rose-400 border-rose-400' : '' }}"
                                                placeholder="{{ $typeLabels[$noteType] }} /{{ $maxScore }}"
                                                @if ($isEfm) x-bind:disabled="absent.{{ $noteType }}" @endif
                                                @input="onScoreInput($event)"
                                            >
                                            @error($scoreField)
                                                @if(filled($message))
                                                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                                @endif
                                            @enderror

                                            @if ($isEfm)
                                                <label class="mt-2 inline-flex items-center gap-2 text-xs font-bold text-slate-600">
                                                    <input
                                                        type="checkbox"
                                                        name="grades[{{ $student->id }}][{{ $noteType }}][absent]"
                                                        value="1"
                                                        class="rounded border-slate-300 text-primary"
                                                        x-model="absent.{{ $noteType }}"
                                                    >
                                                    Absent EFM
                                                </label>
                                                @error($absentField)
                                                    <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                                @enderror
                                            @endif

                                            <input
                                                name="grades[{{ $student->id }}][{{ $noteType }}][observation]"
                                                value="{{ old($observationField, $grade?->observation) }}"
                                                class="sc-input mt-2 w-44"
                                                placeholder="Observation"
                                            >
                                            @error($observationField)
                                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 2 + count($activeTypes) }}" class="px-3 py-6 text-center text-sm text-slate-500">Aucun stagiaire dans ce groupe.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 grid gap-3 md:hidden">
                    @forelse ($students as $student)
                        @php
                            $cardAbsentState = collect($activeTypes)
                                ->mapWithKeys(fn ($noteType) => [
                                    $noteType => (bool) old("grades.{$student->id}.{$noteType}.absent", $grades->get($noteType)?->get($student->id)?->absent ?? false),
                                ])
                                ->all();
                        @endphp
                        <article class="rounded-lg border border-slate-200 p-4" x-data="{ absent: @js($cardAbsentState) }">
                            <div class="font-bold text-slate-800">{{ $student->name }}</div>
                            <div class="text-xs text-slate-500">{{ $student->registration_number ?? '-' }}</div>
                            <div class="mt-3 grid gap-3">
                                @foreach ($activeTypes as $noteType)
                                    @php
                                        $grade = $grades->get($noteType)?->get($student->id);
                                        $maxScore = \App\Models\Evaluation::maxScoreFor($noteType);
                                        $isEfm = $noteType === \App\Models\Evaluation::TYPE_EFM;
                                        $scoreField = "grades.{$student->id}.{$noteType}.score";
                                        $absentField = "grades.{$student->id}.{$noteType}.absent";
                                        $observationField = "grades.{$student->id}.{$noteType}.observation";
                                    @endphp
                                    <div class="rounded-lg bg-slate-50 p-3">
                                        <label class="text-xs font-black uppercase text-slate-500">{{ $typeLabels[$noteType] }} /{{ $maxScore }}</label>
                                        <input
                                            type="text"
                                            inputmode="decimal"
                                            name="grades[{{ $student->id }}][{{ $noteType }}][score]"
                                            value="{{ old($scoreField, $grade?->score) }}"
                                            data-grade-score
                                            data-empty="{{ old($scoreField, $grade?->score) === null || old($scoreField, $grade?->score) === '' ? 'true' : 'false' }}"
                                            class="sc-input mt-2 bg-white {{ $errors->has($scoreField) ? 'ring-2 ring-rose-400 border-rose-400' : '' }}"
                                            placeholder="Note / {{ $maxScore }}"
                                            @if ($isEfm) x-bind:disabled="absent.{{ $noteType }}" @endif
                                            @input="onScoreInput($event)"
                                        >
                                        @error($scoreField)
                                            @if(filled($message))
                                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                            @endif
                                        @enderror

                                        @if ($isEfm)
                                            <label class="mt-2 inline-flex items-center gap-2 text-sm font-semibold text-slate-600">
                                                <input
                                                    type="checkbox"
                                                    name="grades[{{ $student->id }}][{{ $noteType }}][absent]"
                                                    value="1"
                                                    class="rounded border-slate-300 text-primary"
                                                    x-model="absent.{{ $noteType }}"
                                                >
                                                Absent EFM
                                            </label>
                                            @error($absentField)
                                                <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                            @enderror
                                        @endif

                                        <input
                                            name="grades[{{ $student->id }}][{{ $noteType }}][observation]"
                                            value="{{ old($observationField, $grade?->observation) }}"
                                            class="sc-input mt-2 bg-white"
                                            placeholder="Observation"
                                        >
                                        @error($observationField)
                                            <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <article class="rounded-lg border border-slate-200 p-4 text-sm text-slate-500">
                            Aucun stagiaire dans ce groupe.
                        </article>
                    @endforelse
                </div>
            </section>

            <div class="sticky bottom-4 z-20 mt-5 flex flex-wrap items-center justify-end gap-2 rounded-xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur">
                <!-- Live progress bar -->
                <div class="flex-1 hidden sm:block">
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span x-text="filledCount + ' / ' + totalCount + ' champs'"></span>
                        <div class="flex-1 h-1.5 rounded-full bg-slate-200 max-w-32">
                            <div class="h-full rounded-full transition-all duration-300"
                                 :class="filledCount >= totalCount ? 'bg-emerald-500' : 'bg-blue-500'"
                                 :style="'width:' + Math.round((filledCount/totalCount)*100) + '%'"></div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="action" value="save" class="sc-btn sc-btn-primary">
                    Enregistrer les notes
                </button>
            </div>
        </form>
    @endif
@push('scripts')
<script>
function gradeForm(total) {
    return {
        totalCount: total,
        filledCount: 0,
        showErrors: true, // starts true if there are server errors

        init() {
            // Count initially filled fields
            this.filledCount = this.$el
                .querySelectorAll('[data-grade-score]')
                .length;

            // Subtract empty ones
            this.$el.querySelectorAll('[data-grade-score]').forEach(input => {
                if (!input.value.trim()) this.filledCount--;
            });

            // Make totalCount accurate (only count visible score inputs)
            this.totalCount = this.$el.querySelectorAll('[data-grade-score]').length;
        },

        onScoreInput(event) {
            // Hide stale server error banner as soon as user starts typing
            this.showErrors = false;

            const input = event.target;
            const wasFilled = input.dataset.empty === 'false';
            const isFilled = input.value.trim() !== '';

            if (isFilled && !wasFilled) {
                this.filledCount++;
                input.dataset.empty = 'false';
                // Remove red ring when user fills the field
                input.classList.remove('ring-2', 'ring-rose-400', 'border-rose-400');
            } else if (!isFilled && wasFilled) {
                this.filledCount--;
                input.dataset.empty = 'true';
            }
        }
    };
}
</script>
@endpush
</x-layouts.app>
