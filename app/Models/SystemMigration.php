<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemMigration extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'database_id',
        'batch',
        'name',
        'operation',
        'table_name',
        'schema_name',
        'sql_up',
        'sql_down',
        'status',
        'error_message',
        'executed_at',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }

    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOfBatch($query, int $batch)
    {
        return $query->where('batch', $batch);
    }

    public function markExecuted(): void
    {
        $this->update([
            'status' => 'executed',
            'executed_at' => now(),
        ]);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
    }

    public function markRolledBack(): void
    {
        $this->update(['status' => 'rolled_back']);
    }
}
