<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Database;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatabaseStepUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Database $database,
        public string $step,
        public int $progress,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('database.' . $this->database->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'step' => $this->step,
            'progress' => $this->progress,
            'database' => [
                'id' => $this->database->id,
                'name' => $this->database->name,
                'status' => $this->database->status,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'step.updated';
    }
}
