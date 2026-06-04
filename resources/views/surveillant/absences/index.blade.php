<x-app-layout>
    <x-slot:header>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-black text-slate-800">Suivi des Absences</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">Supervision et traitement administratif des absences répétées</p>
            </div>
        </div>
    </x-slot:header>

    <div class="space-y-6">
        @if (session('status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 flex items-center gap-3">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="font-bold">{{ session('status') }}</span>
            </div>
        @endif

        <!-- Filter Form -->
        <form method="GET" action="{{ route('surveillant.absences.index') }}" class="sc-card p-5 mb-6 flex flex-wrap items-end gap-4">
            <div>
                <label for="group_id" class="block text-sm font-bold text-slate-700 mb-2">Filtrer par groupe</label>
                <select name="group_id" id="group_id" class="sc-input w-64" onchange="this.form.submit()">
                    <option value="">Tous les groupes</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}" @selected($selectedGroup == $group->id)>
                            {{ $group->name }} ({{ $group->stagiaires_count }} à risque)
                        </option>
                    @endforeach
                </select>
            </div>
            @if($selectedGroup)
                <div>
                    <a href="{{ route('surveillant.absences.index') }}" class="text-sm text-campus-600 font-bold hover:underline">Réinitialiser</a>
                </div>
            @endif
        </form>

        <!-- List -->
        <div class="sc-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 border-b border-slate-200 text-xs uppercase font-bold text-slate-500">
                        <tr>
                            <th scope="col" class="px-6 py-4">Stagiaire</th>
                            <th scope="col" class="px-6 py-4">Groupe</th>
                            <th scope="col" class="px-6 py-4 text-center">Absences</th>
                            <th scope="col" class="px-6 py-4 text-center">Abs. Non Justifiées</th>
                            <th scope="col" class="px-6 py-4 text-center">Note Comport.</th>
                            <th scope="col" class="px-6 py-4 text-center">Statut</th>
                            <th scope="col" class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($followUps as $followUp)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="px-6 py-4 font-bold text-slate-800">
                                    {{ $followUp->stagiaire->name }}
                                </td>
                                <td class="px-6 py-4 font-semibold text-slate-600">
                                    {{ $followUp->group->name }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-slate-700">
                                    {{ $followUp->total_absences }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-full text-xs font-bold {{ $followUp->non_justified_absences >= \App\Models\Setting::get('absence_admin_threshold', 5) ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' }}">
                                        {{ $followUp->non_justified_absences }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold text-slate-800">
                                        {{ $followUp->stagiaire->behaviorScore?->score ?? 20 }}/20
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($followUp->status === 'pending')
                                        <span class="sc-badge bg-rose-100 text-rose-700 border-rose-200">En attente</span>
                                    @elseif($followUp->status === 'under_review')
                                        <span class="sc-badge bg-amber-100 text-amber-700 border-amber-200">En cours par {{ $followUp->reviewer?->name }}</span>
                                    @else
                                        <span class="sc-badge bg-slate-100 text-slate-700 border-slate-200">{{ ucfirst($followUp->status) }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <a href="{{ route('surveillant.absences.show', $followUp) }}" class="sc-btn sc-btn-secondary sc-btn-sm inline-flex items-center gap-2">
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        Détails
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-semibold">
                                    Aucun dossier de suivi en attente.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($followUps->hasPages())
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    {{ $followUps->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
