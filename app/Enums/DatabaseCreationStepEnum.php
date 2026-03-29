<?php

declare(strict_types=1);

namespace App\Enums;

enum DatabaseCreationStepEnum: string
{
    case VALIDATING = 'validating';
    case CREATING = 'creating';
    case CONFIGURING = 'configuring';
    case MIGRATING = 'migrating';
    case PERMISSIONS = 'permissions';
    case TESTING = 'testing';
    case READY = 'ready';

    public function label(): string
    {
        return match ($this) {
            self::VALIDATING => 'Validando',
            self::CREATING => 'Criando',
            self::CONFIGURING => 'Configurando',
            self::MIGRATING => 'Migrações',
            self::PERMISSIONS => 'Permissões',
            self::TESTING => 'Testando',
            self::READY => 'Pronto',
        };
    }

    public function progress(): int
    {
        return match ($this) {
            self::VALIDATING => 14,
            self::CREATING => 28,
            self::CONFIGURING => 42,
            self::MIGRATING => 56,
            self::PERMISSIONS => 71,
            self::TESTING => 85,
            self::READY => 100,
        };
    }
}
