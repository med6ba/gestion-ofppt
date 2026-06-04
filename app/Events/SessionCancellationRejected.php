<?php

namespace App\Events;

use App\Models\SessionCancellationRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCancellationRejected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cancellation;

    public function __construct(SessionCancellationRequest $cancellation)
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
