<?php

namespace App\Events;

use App\Models\AttendanceSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceSessionClosed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $groupId;

    public function __construct(AttendanceSession $session)
    {
        $this->sessionId = $session->timetable_session_id;
        $this->groupId = $session->timetableSession->group_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('timetable.group.' . $this->groupId),
            new PrivateChannel('role.surveillant'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'attendance.session.closed';
    }
}
