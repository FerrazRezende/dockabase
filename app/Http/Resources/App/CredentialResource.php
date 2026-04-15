<?php

declare(strict_types=1);

namespace App\Http\Resources\App;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CredentialResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'permission' => $this->permission->value,
            'permission_label' => $this->permission->label(),
            'description' => $this->description,
            'created_by' => $this->created_by,
            'users_count' => $this->whenCounted('users') ?? ($this->relationLoaded('users') ? $this->users->count() : 0),
            'databases_count' => $this->whenCounted('databases') ?? ($this->relationLoaded('databases') ? $this->databases->count() : 0),
            'users' => $this->whenLoaded('users', fn () => UserResource::collection($this->users)->toArray($request)),
            'databases' => $this->whenLoaded('databases', fn () => DatabaseResource::collection($this->databases)->resolve()),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
