<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\RiskScore;
use App\Models\User;

class RiskScoreService
{
    public function updateFor(User $stagiaire): RiskScore
    {
        $attendanceQuery = Attendance::query()->where('stagiaire_id', $stagiaire->id);
        $attendances = (clone $attendanceQuery)
            ->with('attendanceSession')
            ->get();
        $total = $attendances->count();
        $absences = $attendances->where('status', Attendance::STATUS_ABSENT)->count();
        $late = $attendances->whereIn('status', [
            Attendance::STATUS_LATE_VALIDATED,
            Attendance::STATUS_LATE_REJECTED,
            Attendance::STATUS_SEVERE_LATE_VALIDATED,
            Attendance::STATUS_SEVERE_LATE_REJECTED,
        ])->count();
        $suspicious = AttendanceAttempt::query()->where('stagiaire_id', $stagiaire->id)->count();

        $presentLike = $attendances->whereIn('status', Attendance::acceptedStatuses())->count();
        $attendanceRate = $total > 0 ? round(($presentLike / $total) * 100, 2) : 100;
        $absenceRate = 100 - $attendanceRate;

        $riskPoints = 0;
        $weeklyLateCounts = [];

        foreach ($attendances as $attendance) {
            $riskPoints += match ($attendance->status) {
                Attendance::STATUS_ABSENT => 3,
                Attendance::STATUS_LATE_VALIDATED => 1,
                Attendance::STATUS_SEVERE_LATE_VALIDATED => 2,
                Attendance::STATUS_LATE_REJECTED => 2,
                Attendance::STATUS_SEVERE_LATE_REJECTED => 4,
                default => 0,
            };

            if (in_array($attendance->status, [Attendance::STATUS_LATE_VALIDATED, Attendance::STATUS_LATE_REJECTED], true)) {
                $date = $attendance->attendanceSession?->actual_started_at
                    ?? $attendance->check_in_at
                    ?? $attendance->marked_at
                    ?? $attendance->created_at
                    ?? now();
                $weekKey = $date->copy()->startOfWeek()->toDateString();
                $weeklyLateCounts[$weekKey] = ($weeklyLateCounts[$weekKey] ?? 0) + 1;

                if ($weeklyLateCounts[$weekKey] > 1) {
                    $riskPoints += 2;
                }
            }
        }

        $riskPoints += $suspicious * 3;

        $score = min(100, $riskPoints);
        $level = match (true) {
            $score >= 18 => 'High',
            $score >= 8 => 'Medium',
            default => 'Low',
        };

        $reasons = [];
        if ($absences > 0) {
            $reasons[] = "{$absences} absences";
        }
        if ($late > 0) {
            $reasons[] = "{$late} retards decides";
        }
        if ($suspicious > 0) {
            $reasons[] = "{$suspicious} tentatives suspectes";
        }
        if (array_sum(array_map(fn (int $count) => max(0, $count - 1), $weeklyLateCounts)) > 0) {
            $reasons[] = 'retards repetes dans une meme semaine';
        }
        if ($absenceRate > 0) {
            $reasons[] = round($absenceRate, 1).'% absence rate';
        }

        return RiskScore::query()->updateOrCreate(
            ['stagiaire_id' => $stagiaire->id],
            [
                'score' => $score,
                'level' => $level,
                'absence_count' => $absences,
                'late_count' => $late,
                'suspicious_count' => $suspicious,
                'attendance_rate' => $attendanceRate,
                'reasons' => $reasons,
                'calculated_at' => now(),
            ]
        );
    }

    public function refreshAll(): void
    {
        User::query()
            ->where('role', User::ROLE_STAGIAIRE)
            ->approved()
            ->each(fn (User $stagiaire) => $this->updateFor($stagiaire));
    }
}
