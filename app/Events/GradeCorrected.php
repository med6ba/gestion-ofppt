<?php

namespace App\Events;

use App\Models\Grade;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GradeCorrected implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $studentId;
    public $moduleId;

    public function __construct(Grade $grade)
    {
        $this->studentId = $grade->user_id;
        $this->moduleId = $grade->module_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->studentId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'grade.corrected';
    }
}
