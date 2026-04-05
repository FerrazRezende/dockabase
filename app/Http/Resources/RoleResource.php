<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $permissions = $this->whenLoaded('permissions');
        if ($permissions instanceof \Illuminate\Database\Eloquent\Collection) {
            $permissionsArray = $permissions->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'guard_name' => $p->guard_name,
            ])->toArray();
        } else {
            $permissionsArray = [];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'guard_name' => $this->guard_name,
            'permissions' => $permissionsArray,
            'users_count' => $this->users_count ?? $this->users()->count(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
