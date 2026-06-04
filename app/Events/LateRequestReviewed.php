<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LateRequestReviewed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->attendance->stagiaire_id),
            new PrivateChannel('attendance.session.' . $this->attendance->session->timetable_session_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'late.request.reviewed';
    }
}
