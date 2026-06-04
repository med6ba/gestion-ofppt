<x-layouts.app title="Mes Notes">
    @php
        $format = fn ($value, string $empty = '-') => $value === null ? $empty : number_format((float) $value, 2);
        $validationLabel = function ($summary) {
            if (!$summary->isComplete()) {
                return ['En attente', 'bg-amber-100 text-amber-700'];
            }

            return (float) $summary->moy_module >= 10
                ? ['Validé', 'bg-emerald-100 text-emerald-700']
                : ['Non validé', 'bg-rose-100 text-rose-700'];
        };
    @endphp

    <section class="sc-card p-5">
        <h2 class="text-xl font-black text-slate-800">Mes Notes</h2>
        <p class="mt-1 text-sm text-slate-500">Seules les notes publiées par vos formateurs sont visibles ici.</p>
    </section>

    <div class="mt-6 grid gap-4 xl:grid-cols-2">
        @forelse ($summaries as $summary)
            @php
                [$label, $tone] = $validationLabel($summary);
                $ccTypes = $summary->module?->ccTypes() ?? [\App\Models\Evaluation::TYPE_CC1, \App\Models\Evaluation::TYPE_CC2, \App\Models\Evaluation::TYPE_CC3];
            @endphp
            <article class="sc-card p-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-lg font-black text-slate-800">{{ $summary->module->name }}</h3>
                        <p class="mt-1 text-sm text-slate-500">{{ $summary->formateur->name }} | {{ $summary->group->code }}</p>
                    </div>
                    <span class="sc-badge {{ $tone }}">{{ $label }}</span>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase text-slate-500">CC1</div>
                        <div class="mt-1 text-xl font-black">{{ $format($summary->cc1) }}</div>
                    </div>
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase text-slate-500">CC2</div>
                        <div class="mt-1 text-xl font-black">{{ $format($summary->cc2) }}</div>
                    </div>
                    @if (in_array(\App\Models\Evaluation::TYPE_CC3, $ccTypes, true))
                        <div class="rounded-lg bg-slate-50 p-3">
                            <div class="text-xs font-bold uppercase text-slate-500">CC3</div>
                            <div class="mt-1 text-xl font-black">{{ $format($summary->cc3) }}</div>
                        </div>
                    @endif
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase text-slate-500">EFM /40</div>
                        <div class="mt-1 text-xl font-black">{{ $format($summary->efm) }}</div>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-campus-100 bg-campus-50 p-4 text-campus-800">
                        <div class="text-xs font-bold uppercase">Moy CC /20</div>
                        <div class="mt-1 text-2xl font-black">{{ $format($summary->moy_cc) }}</div>
                    </div>
                    <div class="rounded-xl border border-primary/20 bg-primary/5 p-4 text-primary">
                        <div class="text-xs font-bold uppercase">Moy Module /20</div>
                        <div class="mt-1 text-2xl font-black">{{ $format($summary->moy_module) }}</div>
                    </div>
                </div>

                @if ($summary->getAttribute('observations_text'))
                    <p class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">{{ $summary->getAttribute('observations_text') }}</p>
                @endif
            </article>
        @empty
            <section class="sc-card p-5 xl:col-span-2">
                <p class="text-sm text-slate-500">Aucune note publiée pour le moment.</p>
            </section>
        @endforelse
    </div>
</x-layouts.app>
