<?php

declare(strict_types=1);

namespace App\Enums;

enum UserStatusEnum: string
{
    case ONLINE = 'online';
    case AWAY = 'away';
    case BUSY = 'busy';
    case OFFLINE = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::ONLINE => 'Online',
            self::AWAY => 'Ausente',
            self::BUSY => 'Ocupado',
            self::OFFLINE => 'Offline',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ONLINE => '#22c55e',
            self::AWAY => '#eab308',
            self::BUSY => '#ef4444',
            self::OFFLINE => '#6b7280',
        };
    }

    public static function all(): array
    {
        return [
            'online' => self::ONLINE,
            'away' => self::AWAY,
            'busy' => self::BUSY,
            'offline' => self::OFFLINE,
        ];
    }
}
