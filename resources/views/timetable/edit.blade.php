<x-layouts.app title="Edit Session">
    <section class="max-w-xl sc-card p-5">
        <a href="{{ route('timetable.index') }}" class="text-sm font-semibold text-campus-700">Back to timetable</a>
        <h2 class="mt-4 text-lg font-bold">Update session</h2>
        <form method="POST" action="{{ route('timetable.update', $session) }}" class="mt-4 grid gap-3">
            @csrf
            @method('PUT')
            @include('timetable.partials.form', ['session' => $session])
            <button class="sc-btn sc-btn-primary">Update session</button>
        </form>
    </section>
</x-layouts.app>
