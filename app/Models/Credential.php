<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CredentialPermissionEnum;
use App\Models\User;
use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Credential extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'name',
        'permission',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'permission' => CredentialPermissionEnum::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'credential_user', 'credential_id', 'user_id')
            ->withTimestamps();
    }

    public function databases(): BelongsToMany
    {
        return $this->belongsToMany(Database::class, 'credential_database', 'credential_id', 'database_id')
            ->withTimestamps();
    }

    public function hasReadPermission(): bool
    {
        return $this->permission === CredentialPermissionEnum::READ
            || $this->permission === CredentialPermissionEnum::READ_WRITE;
    }

    public function hasWritePermission(): bool
    {
        return $this->permission === CredentialPermissionEnum::WRITE
            || $this->permission === CredentialPermissionEnum::READ_WRITE;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        $query->where(function ($q) use ($user) {
            $q->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
                ->orWhere('created_by', $user->id);
        });
    }
}
