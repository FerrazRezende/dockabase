<?php

declare(strict_types=1);

namespace App\Http\Resources;

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
            'users_count' => $this->users_count ?? $this->users()->count(),
            'databases_count' => $this->databases_count ?? $this->databases()->count(),
            'users' => UserResource::collection($this->whenLoaded('users') ?? collect)->collection->toArray() ?? [],
            'databases' => DatabaseResource::collection($this->whenLoaded('databases') ?? collect)->collection->toArray() ?? [],
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
