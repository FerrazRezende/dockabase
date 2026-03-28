<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function credentials(): BelongsToMany
    {
        return $this->belongsToMany(Credential::class, 'credential_database', 'database_id', 'credential_id')
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOfName($query, string $name)
    {
        return $query->where('name', $name);
    }
}
