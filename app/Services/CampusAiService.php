<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceAttempt;
use App\Models\RiskScore;
use App\Models\TimetableSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;

class CampusAiService
{
    public function answer(User $user, string $question): string
    {
        $context = $this->contextFor($user);
        $apiKey = config('smartcampus.groq.api_key');

        if (!$apiKey) {
            return $this->fallbackAnswer($user, $question, $context);
        }

        try {
            $response = Http::withToken($apiKey)
                ->timeout(12)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => config('smartcampus.groq.model'),
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'You are CampusAI for Smart Campus OFPPT. Answer only from the supplied campus data. Respect the user role and do not expose sensitive data.',
                        ],
                        [
                            'role' => 'user',
                            'content' => "Campus data:\n".json_encode($context, JSON_PRETTY_PRINT)."\n\nQuestion: ".$question,
                        ],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 350,
                ]);

            return $response->json('choices.0.message.content') ?: $this->fallbackAnswer($user, $question, $context);
        } catch (\Throwable) {
            return $this->fallbackAnswer($user, $question, $context);
        }
    }

    public function contextFor(User $user): array
    {
        $todaySessions = TimetableSession::query()
            ->with(['group', 'module', 'room', 'formateur'])
            ->forDate(now())
            ->when($user->isFormateur(), fn ($query) => $query->where('formateur_id', $user->id))
            ->when($user->isStagiaire(), fn ($query) => $query->where('group_id', $user->group_id))
            ->orderBy('starts_at')
            ->get()
            ->map(fn (TimetableSession $session) => [
                'time' => $session->timeLabel(),
                'group' => $session->group->code,
                'module' => $session->module->name,
                'room' => $session->room->code,
                'formateur' => $session->formateur->name,
            ]);

        $base = [
            'user' => [
                'name' => $user->name,
                'role' => $user->roleLabel(),
                'group' => $user->group?->code,
            ],
            'today_sessions' => $todaySessions,
            'unread_notifications' => $user->unreadNotifications()->count(),
        ];

        if ($user->isStagiaire()) {
            $base['attendance'] = [
                'absences' => Attendance::where('stagiaire_id', $user->id)->where('status', Attendance::STATUS_ABSENT)->count(),
                'late_arrivals' => Attendance::where('stagiaire_id', $user->id)->whereIn('status', Attendance::lateStatuses())->count(),
                'risk' => $user->riskScore?->only(['level', 'score', 'reasons']),
            ];
        }

        if ($user->isFormateur()) {
            $base['attendance_summary'] = Attendance::query()
                ->whereHas('session', fn ($query) => $query->where('formateur_id', $user->id))
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        if ($user->isSurveillant() || $user->isDirecteur()) {
            $base['campus'] = [
                'students_at_risk' => RiskScore::where('level', 'High')->count(),
                'suspicious_attempts' => AttendanceAttempt::count(),
                'today_sessions' => TimetableSession::forDate(now())->count(),
            ];
        }

        return $base;
    }

    private function fallbackAnswer(User $user, string $question, array $context): string
    {
        if ($user->isStagiaire()) {
            $sessions = collect($context['today_sessions'])->map(fn ($session) => "{$session['time']} {$session['module']} in {$session['room']}")->join('; ');

            return $sessions
                ? "Today you have: {$sessions}. You also have {$context['unread_notifications']} unread notifications."
                : "You have no scheduled classes today. You have {$context['unread_notifications']} unread notifications.";
        }

        if ($user->isFormateur()) {
            return 'Today you have '.count($context['today_sessions']).' scheduled sessions. Open Attendance to mark presence or generate a QR/code check-in.';
        }

        $campus = $context['campus'] ?? ['students_at_risk' => 0, 'suspicious_attempts' => 0, 'today_sessions' => 0];

        return "Campus summary: {$campus['today_sessions']} sessions today, {$campus['students_at_risk']} high-risk stagiaires, {$campus['suspicious_attempts']} suspicious attendance attempts.";
    }
}
