<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        ];
    }

    public function getAvatarAttribute(): ?string
    {
        return $this->attributes['avatar'] ?? null;
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
     * Credentials that belong to the user.
     */
    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_user', 'user_id', 'credential_id')
            ->withTimestamps();
    }
}
