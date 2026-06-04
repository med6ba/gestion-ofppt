<?php

namespace App\Services;

use App\Models\AbsenceFollowUp;
use App\Models\Attendance;
use App\Models\BehaviorScoreLog;
use App\Models\Setting;
use App\Models\StudentBehaviorScore;
use App\Models\User;

class AbsenceFollowUpService
{
    public function handleAbsenceStatusChange(Attendance $attendance, string $oldStatus, string $newStatus)
    {
        $stagiaire = clone $attendance->stagiaire;
        
        $this->ensureBehaviorScoreExists($stagiaire);

        if ($newStatus === 'absent' && $oldStatus !== 'absent') {
            $this->applyAbsencePenalty($stagiaire, $attendance);
        } elseif ($newStatus === 'justified' && $oldStatus === 'absent') {
            $this->restoreAbsencePenalty($stagiaire, $attendance);
        } elseif ($newStatus === 'late_validated' && $oldStatus !== 'late_validated') {
            $this->applyLatePenalty($stagiaire, $attendance);
        }

        $this->checkThresholds($stagiaire);
    }

    protected function ensureBehaviorScoreExists(User $stagiaire)
    {
        if (!$stagiaire->behaviorScore) {
            $initialScore = Setting::get('conduct_score_initial', 20);
            StudentBehaviorScore::create([
                'stagiaire_id' => $stagiaire->id,
                'score' => $initialScore,
                'initial_score' => $initialScore,
            ]);
            $stagiaire->load('behaviorScore');
        }
    }

    protected function applyAbsencePenalty(User $stagiaire, Attendance $attendance)
    {
        $penalty = Setting::get('non_justified_absence_penalty', 1);
        $this->adjustScore($stagiaire, -$penalty, 'absence_non_justified', "Absence non justifiée (Session {$attendance->timetable_session_id})");
    }

    protected function restoreAbsencePenalty(User $stagiaire, Attendance $attendance)
    {
        $penalty = Setting::get('non_justified_absence_penalty', 1);
        $this->adjustScore($stagiaire, $penalty, 'justification_accepted', "Absence justifiée (Session {$attendance->timetable_session_id})");
    }

    protected function applyLatePenalty(User $stagiaire, Attendance $attendance)
    {
        $penalty = Setting::get('late_penalty', 0.25);
        $this->adjustScore($stagiaire, -$penalty, 'late_repeated', "Retard (Session {$attendance->timetable_session_id})");
    }

    protected function adjustScore(User $stagiaire, float $points, string $type, string $reason)
    {
        $scoreRecord = $stagiaire->behaviorScore;
        $oldScore = $scoreRecord->score;
        $newScore = max(0, min($scoreRecord->initial_score, $oldScore + $points));

        if ($oldScore != $newScore) {
            $scoreRecord->update(['score' => $newScore]);

            BehaviorScoreLog::create([
                'stagiaire_id' => $stagiaire->id,
                'type' => $type,
                'points' => $points,
                'old_score' => $oldScore,
                'new_score' => $newScore,
                'reason' => $reason,
                'created_by' => auth()->id() ?? null,
            ]);
        }
    }

    protected function checkThresholds(User $stagiaire)
    {
        $nonJustifiedCount = Attendance::where('stagiaire_id', $stagiaire->id)
            ->where('status', 'absent')
            ->count();

        $totalAbsences = Attendance::where('stagiaire_id', $stagiaire->id)
            ->whereIn('status', ['absent', 'justified'])
            ->count();

        $adminThreshold = Setting::get('absence_admin_threshold', 5);

        if ($nonJustifiedCount >= $adminThreshold) {
            $existing = AbsenceFollowUp::where('stagiaire_id', $stagiaire->id)
                ->whereIn('status', ['pending', 'under_review'])
                ->first();

            if (!$existing) {
                AbsenceFollowUp::create([
                    'stagiaire_id' => $stagiaire->id,
                    'group_id' => $stagiaire->group_id,
                    'total_absences' => $totalAbsences,
                    'non_justified_absences' => $nonJustifiedCount,
                    'status' => 'pending',
                    'created_by_system' => true,
                ]);
            } else {
                $existing->update([
                    'total_absences' => $totalAbsences,
                    'non_justified_absences' => $nonJustifiedCount,
                ]);
            }
        }
    }
}
