<?php

declare(strict_types=1);

namespace App\Enums;

enum CredentialPermissionEnum: string
{
    case READ = 'read';
    case WRITE = 'write';
    case READ_WRITE = 'read-write';

    public function label(): string
    {
        return match ($this) {
            self::READ => __('Read Only'),
            self::WRITE => __('Write Only'),
            self::READ_WRITE => __('Read & Write'),
        };
    }
}
