<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserActivityTypeEnum;
use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    use HasFactory, HasKsuid;

    protected $fillable = [
        'user_id',
        'activity_type',
        'from_status',
        'to_status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => UserActivityTypeEnum::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeStatusChanged($query)
    {
        return $query->where('activity_type', UserActivityTypeEnum::StatusChanged);
    }

    public function scopeDatabaseCreated($query)
    {
        return $query->where('activity_type', UserActivityTypeEnum::DatabaseCreated);
    }

    public function scopeCredentialCreated($query)
    {
        return $query->where('activity_type', UserActivityTypeEnum::CredentialCreated);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
