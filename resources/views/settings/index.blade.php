<x-layouts.app title="Paramètres">
    @php
        $user = auth()->user();
        $presenceHref = $user->isDirecteur() || $user->isSurveillant()
            ? route('attendance.reports')
            : route('attendance.leaderboard');
        $presenceBody = $user->isDirecteur() || $user->isSurveillant()
            ? 'Fenetre QR, retard normal, retard important et rapports globaux.'
            : 'Votre progression Presence XP et les indicateurs accessibles a votre role.';
    @endphp

    <section class="sc-card p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Paramètres</h2>
                <p class="mt-1 text-sm text-slate-500">Centre de configuration Smart Campus OFPPT.</p>
            </div>
            <span class="sc-badge bg-slate-100 text-slate-700">{{ $user->roleLabel() }}</span>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('profile.show', $user) }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="flex items-center gap-2 font-bold text-slate-800">
                    <x-ui.icon name="profile" size="size-4" />
                    Profil
                </div>
                <div class="mt-1 text-sm text-slate-500">Consulter votre compte, statut et informations de role.</div>
            </a>
            <a href="{{ $presenceHref }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="font-bold text-slate-800">Parametres presence</div>
                <div class="mt-1 text-sm text-slate-500">{{ $presenceBody }}</div>
            </a>
            <a href="{{ route('notifications.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="font-bold text-slate-800">Notifications</div>
                <div class="mt-1 text-sm text-slate-500">Voir les alertes et messages systeme.</div>
            </a>
            <a href="{{ route('ai.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="font-bold text-slate-800">CampusAI</div>
                <div class="mt-1 text-sm text-slate-500">Assistant campus limite aux donnees autorisees pour votre role.</div>
            </a>
        </div>
    </section>
</x-layouts.app>
