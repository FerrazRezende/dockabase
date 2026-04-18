<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatabaseTableMetadata extends Model
{
    use HasFactory, HasKsuid, SoftDeletes;

    protected $fillable = [
        'database_id',
        'schema_name',
        'table_name',
        'columns',
        'validations',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'validations' => 'array',
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

    public function scopeOfSchema($query, string $schema)
    {
        return $query->where('schema_name', $schema);
    }

    public function scopeOfTable($query, string $table)
    {
        return $query->where('table_name', $table);
    }
}
