<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StagiaireApproved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stagiaire;

    public function __construct(User $stagiaire)
    {
        $this->stagiaire = $stagiaire;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->stagiaire->id),
            new PrivateChannel('group.' . $this->stagiaire->group_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stagiaire.approved';
    }
}
