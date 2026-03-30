<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
