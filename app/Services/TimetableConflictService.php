<?php

namespace App\Services;

use App\Models\TimetableSession;

class TimetableConflictService
{
    public function firstConflict(array $data, ?int $ignoreId = null): ?string
    {
        $conflicts = $this->allConflicts($data, $ignoreId);
        return empty($conflicts) ? null : $conflicts[0];
    }

    public function allConflicts(array $data, ?int $ignoreId = null): array
    {
        $conflicts = [];

        if (isset($data['starts_at'], $data['ends_at']) && $data['ends_at'] <= $data['starts_at']) {
            $conflicts[] = 'L\'heure de fin doit être après l\'heure de début.';
        }

        $base = TimetableSession::query()
            ->where('status', '!=', 'cancelled')
            ->where('day_of_week', $data['day_of_week'])
            ->whereDate('starts_on', '<=', $data['ends_on'])
            ->whereDate('ends_on', '>=', $data['starts_on'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        if ($ignoreId) {
            $base->whereKeyNot($ignoreId);
        }

        if ((clone $base)->where('room_id', $data['room_id'])->exists()) {
            $conflicts[] = 'Cette salle est déjà occupée pendant ce créneau.';
        }

        if ((clone $base)->where('formateur_id', $data['formateur_id'])->exists()) {
            $conflicts[] = 'Ce formateur a déjà une séance pendant ce créneau.';
        }

        if ((clone $base)->where('group_id', $data['group_id'])->exists()) {
            $conflicts[] = 'Ce groupe a déjà une séance pendant ce créneau.';
        }

        return $conflicts;
    }
}
