<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DatabaseSchemaHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'database_id',
        'action',
        'table_name',
        'column_name',
        'old_value',
        'new_value',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'old_value' => 'array',
            'new_value' => 'array',
        ];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeOfTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }
}
