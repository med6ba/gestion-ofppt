<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StagiaireRegistered implements ShouldBroadcastNow
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
            new PrivateChannel('role.surveillant'),
            new PrivateChannel('role.directeur'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stagiaire.registered';
    }
}
