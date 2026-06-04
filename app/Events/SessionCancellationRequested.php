<?php

namespace App\Events;

use App\Models\SessionCancellation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancellationRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cancellation;

    public function __construct(SessionCancellation $cancellation)
    {
        $this->cancellation = $cancellation;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('role.surveillant'),
            new PrivateChannel('role.directeur'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.cancellation.requested';
    }
}
