<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DeviceConflictDetected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sessionId;
    public $attemptId;
    public $student;

    /**
     * Create a new event instance.
     */
    public function __construct($sessionId, $attemptId, $studentName, $studentId)
    {
        $this->sessionId = $sessionId;
        $this->attemptId = $attemptId;
        $this->student = [
            'id' => $studentId,
            'name' => $studentName,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('attendance.session.' . $this->sessionId),
        ];
    }
}
