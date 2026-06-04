<x-layouts.app title="Évaluations">
    @php
        $query = request()->query();
        $statusTone = fn (?string $status) => $status === 'published'
            ? 'bg-emerald-100 text-emerald-700'
            : 'bg-amber-100 text-amber-700';
        $format = fn ($value, string $empty = '-') => $value === null ? $empty : number_format((float) $value, 2);
    @endphp

    <section class="sc-card p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-black text-slate-800">PV des notes</h2>
                <p class="mt-1 text-sm text-slate-500">Consultation sécurisée par groupe, module et formateur.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('evaluations.export.excel', $query) }}" class="sc-btn sc-btn-secondary">Télécharger Excel</a>
                <a href="{{ route('evaluations.export.pdf', $query) }}" class="sc-btn sc-btn-primary">Télécharger PDF</a>
            </div>
        </div>

        <form method="GET" class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <input name="search" value="{{ $filters['search'] ?? '' }}" class="sc-input" placeholder="Nom ou CEF">
            <select name="group_id" class="sc-input">
                <option value="">Tous les groupes</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}" @selected(($filters['group_id'] ?? '') == $group->id)>{{ $group->code }}</option>
                @endforeach
            </select>
            <select name="module_id" class="sc-input">
                <option value="">Tous les modules</option>
                @foreach ($modules as $module)
                    <option value="{{ $module->id }}" @selected(($filters['module_id'] ?? '') == $module->id)>{{ $module->name }}</option>
                @endforeach
            </select>
            <select name="formateur_id" class="sc-input">
                <option value="">Tous les formateurs</option>
                @foreach ($formateurs as $formateur)
                    <option value="{{ $formateur->id }}" @selected(($filters['formateur_id'] ?? '') == $formateur->id)>{{ $formateur->name }}</option>
                @endforeach
            </select>
            <select name="type" class="sc-input">
                <option value="">Tous les types</option>
                @foreach ($types as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <select name="status" class="sc-input min-w-0">
                    <option value="">Tous les statuts</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="sc-btn sc-btn-primary">Filtrer</button>
            </div>
        </form>
    </section>

    <section class="mt-6 sc-card overflow-hidden">
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">CEF</th>
                        <th class="px-4 py-3">Nom & Prénom</th>
                        <th class="px-4 py-3">Groupe</th>
                        <th class="px-4 py-3">Module</th>
                        <th class="px-4 py-3">Moy CC /20</th>
                        <th class="px-4 py-3">EFM /40</th>
                        <th class="px-4 py-3">Moy Module /20</th>
                        <th class="px-4 py-3">Observations</th>
                        <th class="px-4 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $row->stagiaire->registration_number ?? '-' }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $row->stagiaire->name }}</td>
                            <td class="px-4 py-3">{{ $row->group->code }}</td>
                            <td class="px-4 py-3">{{ $row->module->name }}</td>
                            <td class="px-4 py-3">{{ $format($row->moy_cc) }}</td>
                            <td class="px-4 py-3">{{ $format($row->efm) }}</td>
                            <td class="px-4 py-3 font-bold {{ $row->moy_module !== null && $row->moy_module < 10 ? 'text-rose-700' : 'text-campus-700' }}">{{ $format($row->moy_module) }}</td>
                            <td class="max-w-xs px-4 py-3 text-xs text-slate-500">{{ $row->getAttribute('observations_text') ?: '-' }}</td>
                            <td class="px-4 py-3"><span class="sc-badge {{ $statusTone($row->status) }}">{{ $statuses[$row->status] ?? $row->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-sm text-slate-500">Aucune note publiée pour ces filtres.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="grid gap-3 p-4 md:hidden">
            @forelse ($rows as $row)
                <article class="rounded-lg border border-slate-200 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="font-bold text-slate-800">{{ $row->stagiaire->name }}</div>
                            <div class="text-xs text-slate-500">{{ $row->stagiaire->registration_number ?? '-' }} | {{ $row->group->code }}</div>
                        </div>
                        <span class="sc-badge {{ $statusTone($row->status) }}">{{ $statuses[$row->status] ?? $row->status }}</span>
                    </div>
                    <div class="mt-3 text-sm text-slate-600">{{ $row->module->name }}</div>
                    <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                        <div class="rounded-lg bg-slate-50 p-2"><span class="block text-slate-500">Moy CC</span><b>{{ $format($row->moy_cc) }}</b></div>
                        <div class="rounded-lg bg-slate-50 p-2"><span class="block text-slate-500">EFM</span><b>{{ $format($row->efm) }}</b></div>
                        <div class="rounded-lg bg-campus-50 p-2 text-campus-700"><span class="block">Module</span><b>{{ $format($row->moy_module) }}</b></div>
                    </div>
                    @if ($row->getAttribute('observations_text'))
                        <p class="mt-3 text-xs text-slate-500">{{ $row->getAttribute('observations_text') }}</p>
                    @endif
                </article>
            @empty
                <p class="text-sm text-slate-500">Aucune note publiée pour ces filtres.</p>
            @endforelse
        </div>
    </section>
</x-layouts.app>
