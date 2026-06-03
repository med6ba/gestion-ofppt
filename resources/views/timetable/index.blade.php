<x-layouts.app title="Emploi du Temps">
    @include('timetable.partials.grid', [
        'sessions' => $gridSessions,
        'scheduleLabel' => $scheduleLabel,
        'groups' => $groups,
        'selectedGroupId' => $selectedGroupId,
        'showActions' => true,
    ])

    <div class="mt-6 grid gap-6 xl:grid-cols-[380px_1fr]">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Create session</h2>
            <form method="POST" action="{{ route('timetable.store') }}" class="mt-4 grid gap-3">
                @csrf
                @include('timetable.partials.form', ['session' => null])
                <button class="sc-btn sc-btn-primary">Save session</button>
            </form>
        </section>

        <section class="sc-card p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold">Weekly sessions</h2>
                    <p class="text-sm text-slate-500">Conflicts are blocked before saving.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3">
                @forelse ($sessions as $session)
                    <div class="rounded-lg border border-slate-200 p-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold">{{ $weekDays[$session->day_of_week] }} {{ $session->timeLabel() }}</div>
                                <div class="mt-1 text-sm text-slate-500">{{ $session->group->code }} | {{ $session->module->name }} | {{ $session->room->code }} | {{ $session->formateur->name }}</div>
                                <div class="mt-1 text-xs text-slate-400">{{ $session->starts_on->format('Y-m-d') }} to {{ $session->ends_on->format('Y-m-d') }}</div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('timetable.edit', $session) }}" class="sc-btn sc-btn-secondary">Edit</a>
                                <form method="POST" action="{{ route('timetable.destroy', $session) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="sc-btn sc-btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No sessions yet.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $sessions->links() }}</div>
        </section>
    </div>
</x-layouts.app>
