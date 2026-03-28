<?php

declare(strict_types=1);

namespace App\Enums;

enum CredentialPermissionEnum: string
{
    case Read = 'read';
    case Write = 'write';
    case ReadWrite = 'read-write';

    public function label(): string
    {
        return match ($this) {
            self::Read => 'Read Only',
            self::Write => 'Write Only',
            self::ReadWrite => 'Read & Write',
        };
    }
}
