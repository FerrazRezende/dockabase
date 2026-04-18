<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\User;
use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Database extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'host',
        'port',
        'database_name',
        'is_active',
        'settings',
        'status',
        'current_step',
        'progress',
        'error_message',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
            'progress' => 'integer',
        ];
    }

    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_database', 'database_id', 'credential_id')
            ->withTimestamps();
    }

    public function schemaHistories(): HasMany
    {
        return $this->hasMany(DatabaseSchemaHistory::class);
    }

    public function migrations(): HasMany
    {
        return $this->hasMany(SystemMigration::class);
    }

    public function tableMetadata(): HasMany
    {
        return $this->hasMany(DatabaseTableMetadata::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo($query, User $user)
    {
        $query->where(function ($q) use ($user) {
            $q->whereHas('credentials.users', fn ($q) => $q->where('users.id', $user->id))
                ->orWhere('created_by', $user->id);
        });
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
