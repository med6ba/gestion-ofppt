<x-app-layout>
    <x-slot:header>
        <div class="flex items-center gap-4">
            <a href="{{ route('surveillant.absences.index') }}" class="rounded-full bg-slate-100 p-2 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition">
                <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
            </a>
            <div>
                <h1 class="text-2xl font-black text-slate-800">Dossier de suivi administratif</h1>
                <p class="mt-1 text-sm font-semibold text-slate-500">{{ $followUp->stagiaire->name }} - {{ $followUp->group->name }}</p>
            </div>
        </div>
    </x-slot:header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left Column: Details & Form -->
        <div class="lg:col-span-2 space-y-6">
            
            <div class="sc-card p-6 flex items-start gap-6">
                <div class="size-16 rounded-full bg-slate-100 flex items-center justify-center text-xl font-bold text-slate-600 shrink-0">
                    {{ substr($followUp->stagiaire->name, 0, 1) }}
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-bold text-slate-800">{{ $followUp->stagiaire->name }}</h2>
                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-slate-500 font-semibold block">CEF</span>
                            <span class="font-bold text-slate-700">{{ $followUp->stagiaire->registration_number ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-semibold block">Groupe</span>
                            <span class="font-bold text-slate-700">{{ $followUp->group->name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-semibold block">Absences Totales</span>
                            <span class="font-bold text-slate-700">{{ $followUp->total_absences }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500 font-semibold block">Abs. Non Justifiées</span>
                            <span class="font-bold text-rose-600">{{ $followUp->non_justified_absences }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Form -->
            <div class="sc-card p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-3">Décision Administrative</h3>
                
                @if(in_array($followUp->status, ['pending', 'under_review']))
                    <form method="POST" action="{{ route('surveillant.absences.resolve', $followUp) }}">
                        @csrf
                        
                        <div class="mb-5">
                            <label for="decision_note" class="block text-sm font-semibold text-slate-700 mb-2">Note / Décision <span class="text-rose-500">*</span></label>
                            <textarea name="decision_note" id="decision_note" rows="4" class="sc-input w-full" placeholder="Ex: Engagement signé par l'étudiant, dernier avertissement..." required></textarea>
                        </div>

                        <div class="flex items-center gap-4">
                            <x-confirmation-modal title="Marquer comme Résolu" message="Êtes-vous sûr de vouloir marquer ce dossier comme résolu ?" confirmText="Résoudre" type="primary" class="flex-1">
                                <button type="submit" name="action" value="resolved" class="sc-btn sc-btn-primary w-full">
                                    Marquer comme Résolu
                                </button>
                            </x-confirmation-modal>
                            <x-confirmation-modal title="Refuser / Suspendre" message="Êtes-vous sûr de vouloir refuser ou suspendre ce dossier ?" confirmText="Refuser" type="danger" class="flex-1">
                                <button type="submit" name="action" value="rejected" class="sc-btn sc-btn-secondary w-full border-rose-200 text-rose-700 hover:bg-rose-50 hover:border-rose-300">
                                    Refuser / Suspendre
                                </button>
                            </x-confirmation-modal>
                        </div>
                    </form>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
                        <div class="flex items-center justify-between mb-4">
                            <span class="sc-badge {{ $followUp->status === 'resolved' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200' }}">
                                Dossier {{ $followUp->status === 'resolved' ? 'Résolu' : 'Refusé' }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500">Traité le {{ $followUp->reviewed_at?->format('d/m/Y H:i') }} par {{ $followUp->reviewer?->name }}</span>
                        </div>
                        
                        <div>
                            <span class="text-sm font-semibold text-slate-500 block mb-1">Note de décision</span>
                            <p class="text-slate-800 bg-white border border-slate-200 p-4 rounded-lg shadow-sm whitespace-pre-wrap">{{ $followUp->decision_note }}</p>
                        </div>
                    </div>
                @endif
            </div>

        </div>

        <!-- Right Column: Behavior Score -->
        <div class="space-y-6">
            <div class="sc-card p-6 text-center">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-2">Note de Comportement</h3>
                <div class="text-6xl font-black {{ ($followUp->stagiaire->behaviorScore?->score ?? 20) < 10 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $followUp->stagiaire->behaviorScore?->score ?? 20 }}<span class="text-3xl text-slate-400">/20</span>
                </div>
            </div>

            <div class="sc-card overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="font-bold text-slate-800">Historique Comportement</h3>
                </div>
                <div class="p-5">
                    @if($followUp->stagiaire->behaviorScoreLogs->isEmpty())
                        <p class="text-sm text-slate-500 text-center italic">Aucun historique disponible.</p>
                    @else
                        <ul class="space-y-4">
                            @foreach($followUp->stagiaire->behaviorScoreLogs as $log)
                                <li class="flex items-start gap-3">
                                    <div class="shrink-0 mt-1">
                                        @if($log->points > 0)
                                            <span class="flex size-6 rounded-full bg-emerald-100 items-center justify-center text-emerald-600">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 15l7-7 7 7" /></svg>
                                            </span>
                                        @else
                                            <span class="flex size-6 rounded-full bg-rose-100 items-center justify-center text-rose-600">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" /></svg>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-slate-800">{{ $log->reason }}</p>
                                        <p class="text-xs text-slate-500">{{ $log->created_at->format('d/m/Y H:i') }} • Score: {{ $log->old_score }} &rarr; {{ $log->new_score }}</p>
                                    </div>
                                    <div class="text-sm font-bold {{ $log->points > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                        {{ $log->points > 0 ? '+' : '' }}{{ $log->points }}
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
