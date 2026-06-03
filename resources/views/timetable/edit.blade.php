<x-layouts.app title="Modifier une Seance">
    <section class="max-w-xl sc-card p-5">
        <a href="{{ route('timetable.index', ['week_start' => $session->starts_on?->copy()->startOfWeek()?->toDateString(), 'group_id' => $session->group_id]) }}" class="text-sm font-semibold text-campus-700">Retour a l'emploi du temps</a>
        <h2 class="mt-4 text-lg font-bold">Mettre a jour la seance</h2>
        <form method="POST" action="{{ route('timetable.update', $session) }}" class="mt-4 grid gap-3">
            @csrf
            @method('PUT')
            @include('timetable.partials.form', ['session' => $session])
            <button class="sc-btn sc-btn-primary">Mettre a jour</button>
        </form>
    </section>
</x-layouts.app>
