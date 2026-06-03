@php
    $defaultWeekStart = $selectedWeekStart ?? now()->startOfWeek();
    $defaultGroupId = $selectedGroupId ?? null;
    $defaultDay = min(now()->dayOfWeekIso, 6);
@endphp

<div>
    <label class="sc-label">Groupe</label>
    <select class="sc-input mt-1" name="group_id" required>
        @foreach ($groups as $group)
            <option value="{{ $group->id }}" @selected(old('group_id', $session?->group_id ?? $defaultGroupId) == $group->id)>{{ $group->code }} - {{ $group->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="sc-label">Module</label>
    <select class="sc-input mt-1" name="module_id" required>
        @foreach ($modules as $module)
            <option value="{{ $module->id }}" @selected(old('module_id', $session?->module_id) == $module->id)>{{ $module->code }} - {{ $module->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="sc-label">Formateur</label>
    <select class="sc-input mt-1" name="formateur_id" required>
        @foreach ($formateurs as $formateur)
            <option value="{{ $formateur->id }}" @selected(old('formateur_id', $session?->formateur_id) == $formateur->id)>{{ $formateur->name }}</option>
        @endforeach
    </select>
</div>
<div>
    <label class="sc-label">Salle</label>
    <select class="sc-input mt-1" name="room_id" required>
        @foreach ($rooms as $room)
            <option value="{{ $room->id }}" @selected(old('room_id', $session?->room_id) == $room->id)>{{ $room->code }} - {{ $room->name }}</option>
        @endforeach
    </select>
</div>
<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="sc-label">Jour</label>
        <select class="sc-input mt-1" name="day_of_week" required>
            @foreach ($weekDays as $value => $label)
                <option value="{{ $value }}" @selected(old('day_of_week', $session?->day_of_week ?? $defaultDay) == $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="sc-label">Semaine</label>
        <input class="sc-input mt-1" name="starts_on" type="date" value="{{ old('starts_on', $session?->starts_on?->format('Y-m-d') ?? $defaultWeekStart->format('Y-m-d')) }}" required>
    </div>
</div>
<div class="grid gap-3 sm:grid-cols-2">
    <div>
        <label class="sc-label">Debut</label>
        <input class="sc-input mt-1" name="starts_at" type="time" value="{{ old('starts_at', $session?->starts_at ? substr($session->starts_at, 0, 5) : '08:30') }}" required>
    </div>
    <div>
        <label class="sc-label">Fin</label>
        <input class="sc-input mt-1" name="ends_at" type="time" value="{{ old('ends_at', $session?->ends_at ? substr($session->ends_at, 0, 5) : '10:30') }}" required>
    </div>
</div>
<div>
    <label class="sc-label">Note de modification</label>
    <textarea class="sc-input mt-1" name="change_note" rows="3">{{ old('change_note', $session?->change_note) }}</textarea>
</div>
