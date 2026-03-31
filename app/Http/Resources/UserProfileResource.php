<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Pennant\Feature;

class UserProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Get all permissions (from roles + direct)
        $allPermissions = $this->getAllPermissions();
        
        // Build permission list with source
        $permissionsWithSource = $allPermissions->map(function ($permission) {
            $source = 'direct';
            foreach ($this->roles as $role) {
                if ($role->permissions->contains($permission)) {
                    $source = "role:{$role->name}";
                    break;
                }
            }
            return [
                'name' => $permission->name,
                'source' => $source,
            ];
        });

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
                    'permission' => $credential->permission,
                ];
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'active' => $this->active,
            'password_changed_at' => $this->password_changed_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'direct_permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            'all_permissions' => $permissionsWithSource,
            
            'features' => $features,
            'credentials' => CredentialResource::collection($this->whenLoaded('credentials')),
            'databases' => $databases,
        ];
    }
}
