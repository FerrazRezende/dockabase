<?php

declare(strict_types=1);

namespace App\Enums;

enum RolloutStrategyEnum: string
{
    case Inactive = 'inactive';
    case Percentage = 'percentage';
    case Users = 'users';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Inactive => 'Inativo',
            self::Percentage => 'Percentual',
            self::Users => 'Usuários Específicos',
            self::All => 'Todos',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Inactive;
    }
}
