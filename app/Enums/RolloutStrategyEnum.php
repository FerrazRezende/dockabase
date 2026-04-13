<?php

declare(strict_types=1);

namespace App\Enums;

enum RolloutStrategyEnum: string
{
    case INACTIVE = 'inactive';
    case PERCENTAGE = 'percentage';
    case USERS = 'users';
    case ALL = 'all';

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => __('Inactive'),
            self::PERCENTAGE => __('Percentage'),
            self::USERS => __('Specific Users'),
            self::ALL => __('All'),
        };
    }

    public function isActive(): bool
    {
        return $this !== self::INACTIVE;
    }
}
