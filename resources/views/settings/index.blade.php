<x-layouts.app title="Paramètres">
    <section class="sc-card p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-bold text-slate-800">Paramètres</h2>
                <p class="mt-1 text-sm text-slate-500">Centre de configuration Smart Campus OFPPT.</p>
            </div>
            <span class="sc-badge bg-slate-100 text-slate-700">{{ auth()->user()->roleLabel() }}</span>
        </div>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            <a href="{{ auth()->user()->isDirecteur() || auth()->user()->isSurveillant() ? route('attendance.reports') : route('attendance.leaderboard') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="font-bold text-slate-800">Parametres presence</div>
                <div class="mt-1 text-sm text-slate-500">Fenetre QR, retard normal et retard important.</div>
            </a>
            <a href="{{ route('notifications.index') }}" class="rounded-lg border border-slate-200 p-4 hover:bg-slate-50">
                <div class="font-bold text-slate-800">Notifications</div>
                <div class="mt-1 text-sm text-slate-500">Voir les alertes et messages systeme.</div>
            </a>
        </div>
    </section>
</x-layouts.app>
