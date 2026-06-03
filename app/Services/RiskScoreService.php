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
        $total = (clone $attendanceQuery)->count();
        $absences = (clone $attendanceQuery)->where('status', 'absent')->count();
        $late = (clone $attendanceQuery)->where('status', 'late')->count();
        $suspicious = AttendanceAttempt::query()->where('stagiaire_id', $stagiaire->id)->count();

        $presentLike = max($total - $absences, 0);
        $attendanceRate = $total > 0 ? round(($presentLike / $total) * 100, 2) : 100;
        $absenceRate = 100 - $attendanceRate;

        $score = min(100, (int) round(($absences * 8) + ($late * 3) + ($suspicious * 10) + ($absenceRate * 0.5)));
        $level = match (true) {
            $score >= 70 => 'High',
            $score >= 35 => 'Medium',
            default => 'Low',
        };

        $reasons = [];
        if ($absences > 0) {
            $reasons[] = "{$absences} absences";
        }
        if ($late > 0) {
            $reasons[] = "{$late} late arrivals";
        }
        if ($suspicious > 0) {
            $reasons[] = "{$suspicious} suspicious attempts";
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
