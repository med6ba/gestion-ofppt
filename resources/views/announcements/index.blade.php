<x-layouts.app title="Annonces">
    @php
        $user = auth()->user();
        $cards = [
            ['title' => 'Annonces administratives', 'body' => 'Messages officiels de la direction et du surveillant general.', 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
            ['title' => 'Annonces pedagogiques', 'body' => $user->isStagiaire() ? 'Informations liees a votre groupe et a vos modules.' : 'Informations a diffuser aux groupes et aux modules suivis.', 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
            ['title' => 'Historique', 'body' => 'Les annonces restent separees du chat pour garder les communications officielles lisibles.', 'roles' => ['directeur', 'surveillant', 'formateur', 'stagiaire']],
        ];
    @endphp

    <section class="sc-card p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Annonces</h2>
                <p class="mt-1 text-sm text-slate-500">Espace commun pour les communications officielles du campus.</p>
            </div>
            <span class="sc-badge bg-campus-50 text-campus-700">{{ $user->roleLabel() }}</span>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-3">
            @foreach ($cards as $card)
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <div class="text-sm font-bold text-slate-700">{{ $card['title'] }}</div>
                    <div class="mt-2 text-sm text-slate-500">{{ $card['body'] }}</div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.app>
