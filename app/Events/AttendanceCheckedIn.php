<?php

namespace App\Events;

use App\Models\Attendance;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceCheckedIn implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $attendance;

    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
        $this->sessionId = $attendance->session->timetable_session_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('attendance.session.' . $this->sessionId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.checked.in';
    }
}
