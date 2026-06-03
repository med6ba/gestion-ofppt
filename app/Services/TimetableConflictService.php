<?php

namespace App\Services;

use App\Models\TimetableSession;

class TimetableConflictService
{
    public function firstConflict(array $data, ?int $ignoreId = null): ?string
    {
        $base = TimetableSession::query()
            ->where('day_of_week', $data['day_of_week'])
            ->whereDate('starts_on', '<=', $data['ends_on'])
            ->whereDate('ends_on', '>=', $data['starts_on'])
            ->where('starts_at', '<', $data['ends_at'])
            ->where('ends_at', '>', $data['starts_at']);

        if ($ignoreId) {
            $base->whereKeyNot($ignoreId);
        }

        if ((clone $base)->where('room_id', $data['room_id'])->exists()) {
            return 'This room is already occupied during the selected time.';
        }

        if ((clone $base)->where('formateur_id', $data['formateur_id'])->exists()) {
            return 'This formateur already has another session during this time.';
        }

        if ((clone $base)->where('group_id', $data['group_id'])->exists()) {
            return 'This group already has a session during this time.';
        }

        return null;
    }
}
