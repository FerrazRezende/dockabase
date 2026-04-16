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

final class UserRemovedFromCredential implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly Credential $credential,
        public readonly User $removedBy,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('users.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.removed-from-credential';
    }

    public function broadcastWith(): array
    {
        return [
            'credential_id' => $this->credential->id,
            'credential_name' => $this->credential->name,
            'removed_by' => [
                'id' => $this->removedBy->id,
                'name' => $this->removedBy->name,
            ],
        ];
    }
}
