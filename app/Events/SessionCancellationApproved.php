<?php

namespace App\Events;

use App\Models\SessionCancellation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancellationApproved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cancellation;
    public $groupId;

    public function __construct(SessionCancellation $cancellation)
    {
        $this->cancellation = $cancellation;
        $this->groupId = $cancellation->session->group_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('timetable.group.' . $this->groupId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.cancellation.approved';
    }
}
