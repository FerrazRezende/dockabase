<?php

declare(strict_types=1);

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Pennant\Feature;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get features active for this user
        $features = [];
        $featureNames = [
            'database-creator',
            'schema-builder',
            'table-manager',
            'dynamic-api',
            'realtime',
            'storage',
            'otp-auth',
        ];

        foreach ($featureNames as $feature) {
            if (Feature::for($this->resource)->active($feature)) {
                $features[] = $feature;
            }
        }

        // Get databases via credentials
        $databases = [];
        foreach ($this->credentials as $credential) {
            foreach ($credential->databases as $database) {
                $databases[] = [
                    'id' => $database->id,
                    'name' => $database->name,
                    'credential' => $credential->name,
                    'permission' => $credential->permission->value,
                ];
            }
        }

        // Process roles manually to avoid {data: [...]} structure
        $rolesArray = [];
        foreach ($this->whenLoaded('roles') ?? collect() as $role) {
            $permissionsArray = [];
            foreach ($role->permissions ?? collect() as $perm) {
                $permissionsArray[] = [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'guard_name' => $perm->guard_name,
                ];
            }
            $rolesArray[] = [
                'id' => $role->id,
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'permissions' => $permissionsArray,
                'users_count' => $role->users_count ?? $role->users()->count(),
                'created_at' => $role->created_at->toISOString(),
                'updated_at' => $role->updated_at->toISOString(),
            ];
        }

        // Process direct permissions manually
        $directPermissionsArray = [];
        foreach ($this->whenLoaded('permissions') ?? collect() as $perm) {
            $directPermissionsArray[] = [
                'id' => $perm->id,
                'name' => $perm->name,
                'guard_name' => $perm->guard_name,
            ];
        }

        // Process credentials manually
        $credentialsArray = [];
        foreach ($this->whenLoaded('credentials') ?? collect() as $cred) {
            $credentialsArray[] = [
                'id' => $cred->id,
                'name' => $cred->name,
                'permission' => $cred->permission->value,
                'permission_label' => $cred->permission->label(),
                'description' => $cred->description,
                'users_count' => $cred->users_count ?? $cred->users()->count(),
                'databases_count' => $cred->databases_count ?? $cred->databases()->count(),
                'created_at' => $cred->created_at->toISOString(),
                'updated_at' => $cred->updated_at->toISOString(),
            ];
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'is_admin' => $this->is_admin,
            'active' => $this->active,
            'status' => app(\App\Services\UserStatusService::class)->getStatus($this->resource)->value,
            'password_changed_at' => $this->password_changed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            'roles' => $rolesArray,
            'direct_permissions' => $directPermissionsArray,
            'denied_permissions' => $this->denied_permissions ?? [],

            'features' => $features,
            'credentials' => $credentialsArray,
            'databases' => $databases,
        ];
    }
}
