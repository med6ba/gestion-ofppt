<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TimetableUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $groupId;

    public function __construct(int $groupId)
    {
        $this->groupId = $groupId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('timetable.group.' . $this->groupId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'timetable.updated';
    }
}
