<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CredentialPermissionEnum;
use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Credential extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'name',
        'permission',
        'description',
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
        return $this->permission === CredentialPermissionEnum::Read
            || $this->permission === CredentialPermissionEnum::ReadWrite;
    }

    public function hasWritePermission(): bool
    {
        return $this->permission === CredentialPermissionEnum::Write
            || $this->permission === CredentialPermissionEnum::ReadWrite;
    }
}
