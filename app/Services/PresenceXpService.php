<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\PresenceXpLog;
use App\Models\StudentPresenceProfile;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class PresenceXpService
{
    public function refreshFor(User $stagiaire): StudentPresenceProfile
    {
        $attendances = Attendance::query()
            ->with('attendanceSession')
            ->where('stagiaire_id', $stagiaire->id)
            ->orderByRaw('COALESCE(check_in_at, marked_at, created_at)')
            ->get();

        $weeklyNormalLateCounts = [];
        $xpPoints = 0;

        foreach ($attendances as $attendance) {
            [$points, $reason] = $this->pointsFor($attendance, $weeklyNormalLateCounts);
            $xpPoints += $points;

            PresenceXpLog::query()->updateOrCreate(
                ['attendance_id' => $attendance->id],
                [
                    'stagiaire_id' => $stagiaire->id,
                    'points' => $points,
                    'reason' => $reason,
                    'created_at' => $this->attendanceDate($attendance),
                ]
            );
        }

        $absenceCount = $attendances->where('status', Attendance::STATUS_ABSENT)->count();
        $lateCount = $attendances->whereIn('status', [
            Attendance::STATUS_LATE_PENDING,
            Attendance::STATUS_LATE_VALIDATED,
            Attendance::STATUS_LATE_REJECTED,
        ])->count();
        $severeLateCount = $attendances->whereIn('status', Attendance::severeLateStatuses())->count();

        return StudentPresenceProfile::query()->updateOrCreate(
            ['stagiaire_id' => $stagiaire->id],
            [
                'xp_points' => $xpPoints,
                'attendance_streak' => $this->attendanceStreak($attendances),
                'absence_count' => $absenceCount,
                'late_count' => $lateCount,
                'severe_late_count' => $severeLateCount,
                'rank_level' => $this->rankLevel($xpPoints),
                'updated_at' => now(),
            ]
        );
    }

    public function refreshAll(): void
    {
        User::query()
            ->where('role', User::ROLE_STAGIAIRE)
            ->approved()
            ->each(fn (User $stagiaire) => $this->refreshFor($stagiaire));
    }

    private function pointsFor(Attendance $attendance, array &$weeklyNormalLateCounts): array
    {
        return match ($attendance->status) {
            Attendance::STATUS_PRESENT => [10, 'Presence QR validee'],
            Attendance::STATUS_ABSENT => [-10, 'Absence constatee'],
            Attendance::STATUS_JUSTIFIED => [0, 'Absence justifiee'],
            Attendance::STATUS_LATE_VALIDATED => $this->normalLatePoints($attendance, $weeklyNormalLateCounts),
            Attendance::STATUS_SEVERE_LATE_VALIDATED => [2, 'Retard important accepte'],
            Attendance::STATUS_SEVERE_LATE_REJECTED => [-15, 'Retard important refuse'],
            Attendance::STATUS_LATE_REJECTED => [-5, 'Retard refuse'],
            Attendance::STATUS_LATE_PENDING,
            Attendance::STATUS_SEVERE_LATE_PENDING,
            Attendance::STATUS_PENDING => [0, 'Decision en attente'],
            default => [0, 'Statut sans impact XP'],
        };
    }

    private function normalLatePoints(Attendance $attendance, array &$weeklyNormalLateCounts): array
    {
        $weekKey = $this->attendanceDate($attendance)->copy()->startOfWeek()->toDateString();
        $weeklyNormalLateCounts[$weekKey] = ($weeklyNormalLateCounts[$weekKey] ?? 0) + 1;
        $count = $weeklyNormalLateCounts[$weekKey];

        if ($count === 1) {
            return [5, 'Premier retard valide de la semaine'];
        }

        if ($count === 2) {
            return [-5, 'Deuxieme retard valide de la semaine'];
        }

        return [-10, 'Retards repetes dans la semaine'];
    }

    private function attendanceDate(Attendance $attendance): Carbon
    {
        return $attendance->attendanceSession?->actual_started_at
            ?? $attendance->check_in_at
            ?? $attendance->marked_at
            ?? $attendance->created_at
            ?? now();
    }

    private function attendanceStreak(Collection $attendances): int
    {
        $streak = 0;

        foreach ($attendances->reverse() as $attendance) {
            if ($attendance->isAccepted()) {
                $streak++;

                continue;
            }

            if (in_array($attendance->status, [Attendance::STATUS_PENDING, Attendance::STATUS_LATE_PENDING, Attendance::STATUS_SEVERE_LATE_PENDING], true)) {
                continue;
            }

            break;
        }

        return $streak;
    }

    private function rankLevel(int $xpPoints): string
    {
        return match (true) {
            $xpPoints >= 300 => 'Platinum',
            $xpPoints >= 150 => 'Gold',
            $xpPoints >= 60 => 'Silver',
            default => 'Bronze',
        };
    }
}
