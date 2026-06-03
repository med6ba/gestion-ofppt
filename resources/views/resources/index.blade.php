<x-layouts.app title="Campus Resources">
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Filieres</h2>
            <form method="POST" action="{{ route('resources.filieres.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_140px_auto]">
                @csrf
                <input class="sc-input" name="name" placeholder="Filiere name" required>
                <input class="sc-input" name="code" placeholder="Code" required>
                <button class="sc-btn sc-btn-primary">Add</button>
            </form>
            <div class="mt-4 grid gap-2">
                @foreach ($filieres as $filiere)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $filiere->code }} - {{ $filiere->name }}</div>
                        <div class="text-sm text-slate-500">{{ $filiere->groups_count }} groups</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Groups</h2>
            <form method="POST" action="{{ route('resources.groups.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <select class="sc-input" name="filiere_id" required>
                    <option value="">Filiere</option>
                    @foreach ($filieres as $filiere)
                        <option value="{{ $filiere->id }}">{{ $filiere->code }}</option>
                    @endforeach
                </select>
                <input class="sc-input" name="name" placeholder="Group name" required>
                <input class="sc-input" name="code" placeholder="Code" required>
                <input class="sc-input" name="year_level" placeholder="Year level">
                <input class="sc-input" name="capacity" type="number" value="30" min="1" max="80" required>
                <button class="sc-btn sc-btn-primary">Add group</button>
            </form>
            <div class="mt-4 grid gap-2">
                @foreach ($groups as $group)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $group->code }} - {{ $group->name }}</div>
                        <div class="text-sm text-slate-500">{{ $group->filiere->code }} | {{ $group->stagiaires_count }} stagiaires</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Modules</h2>
            <form method="POST" action="{{ route('resources.modules.store') }}" class="mt-4 grid gap-3 sm:grid-cols-[1fr_140px_auto]">
                @csrf
                <input class="sc-input" name="name" placeholder="Module name" required>
                <input class="sc-input" name="code" placeholder="Code" required>
                <button class="sc-btn sc-btn-primary">Add</button>
            </form>
            <div class="mt-4 grid gap-2">
                @foreach ($modules as $module)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $module->code }} - {{ $module->name }}</div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="sc-card p-5">
            <h2 class="text-lg font-bold">Rooms</h2>
            <form method="POST" action="{{ route('resources.rooms.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <input class="sc-input" name="name" placeholder="Room name" required>
                <input class="sc-input" name="code" placeholder="Code" required>
                <input class="sc-input" name="capacity" type="number" value="30" min="1" max="200" required>
                <select class="sc-input" name="type" required>
                    <option value="classroom">Classroom</option>
                    <option value="lab">Lab</option>
                    <option value="workshop">Workshop</option>
                    <option value="amphi">Amphi</option>
                </select>
                <button class="sc-btn sc-btn-primary sm:col-span-2">Add room</button>
            </form>
            <div class="mt-4 grid gap-2">
                @foreach ($rooms as $room)
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="font-semibold">{{ $room->code }} - {{ $room->name }}</div>
                        <div class="text-sm text-slate-500">{{ $room->capacity }} seats | {{ $room->type }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
