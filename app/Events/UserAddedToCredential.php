<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Credential;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class UserAddedToCredential implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Credential $credential,
        public readonly User $addedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.added-to-credential';
    }

    public function broadcastWith(): array
    {
        return [
            'credential_id' => $this->credential->id,
            'credential_name' => $this->credential->name,
            'permission' => $this->credential->permission->value,
            'added_by' => [
                'id' => $this->addedBy->id,
                'name' => $this->addedBy->name,
            ],
        ];
    }
}
