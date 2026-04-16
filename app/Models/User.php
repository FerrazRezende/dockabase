<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\UserActivity;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasKsuid;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable, HasKsuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'password_changed_at',
        'active',
        'denied_permissions',
        'locale',
        'avatar',
        'bio',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'password_changed_at' => 'datetime',
            'active' => 'boolean',
            'denied_permissions' => 'array',
            'locale' => 'string',
        ];
    }

    public function getAvatarAttribute(): ?string
    {
        $avatar = $this->attributes['avatar'] ?? null;

        if (!$avatar) {
            return null;
        }

        // Serve avatar through the application proxy endpoint
        // This avoids issues with MinIO presigned URL host mismatches
        return url("/avatars/{$this->id}");
    }

    /**
     * Check if user needs to change password
     */
    public function needsPasswordChange(): bool
    {
        return is_null($this->password_changed_at);
    }

    /**
     * Check if user account is active
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Deactivate user account
     */
    public function deactivate(): void
    {
        $this->active = false;
    }

    /**
     * Activate user account
     */
    public function activate(): void
    {
        $this->active = true;
    }

    /**
     * Check if user has permission, considering denied_permissions.
     * Denied permissions override everything.
     */
    public function checkPermission(string $permission): bool
    {
        // Admins bypass all permission checks (unless explicitly denied)
        if ($this->is_admin) {
            // But denied permissions still override
            $deniedPermissionIds = $this->denied_permissions ?? [];
            if (!empty($deniedPermissionIds)) {
                $deniedNames = SpatiePermission::whereIn('id', $deniedPermissionIds)
                    ->pluck('name')
                    ->toArray();
                if (in_array($permission, $deniedNames, true)) {
                    return false;
                }
            }
            return true;
        }

        // Check denied permissions first (they override everything)
        $deniedPermissionIds = $this->denied_permissions ?? [];
        if (!empty($deniedPermissionIds)) {
            $deniedNames = SpatiePermission::whereIn('id', $deniedPermissionIds)
                ->pluck('name')
                ->toArray();
            if (in_array($permission, $deniedNames, true)) {
                return false;
            }
        }

        return $this->hasPermissionTo($permission);
    }

    /**
     * Get all permissions the user actually has (excluding denied).
     */
    public function getActualPermissions(): Collection
    {
        $deniedPermissionIds = $this->denied_permissions ?? [];
        if (empty($deniedPermissionIds)) {
            return $this->getAllPermissions();
        }

        $deniedNames = SpatiePermission::whereIn('id', $deniedPermissionIds)
            ->pluck('name')
            ->toArray();

        return $this->getAllPermissions()
            ->reject(fn ($perm) => in_array($perm->name, $deniedNames, true));
    }

    /**
     * Credentials that belong to the user.
     */
    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_user', 'user_id', 'credential_id')
            ->withTimestamps();
    }

    /**
     * Activities logged for the user.
     */
    public function activities(): HasMany
    {
        return $this->hasMany(UserActivity::class)->latest();
    }
}
