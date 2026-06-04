<?php

namespace App\Events;

use App\Models\SessionCancellation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancellationRejected implements ShouldBroadcastNow
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
            new PrivateChannel('user.' . $this->cancellation->formateur_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.cancellation.rejected';
    }
}
