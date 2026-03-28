<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Definitions
    |--------------------------------------------------------------------------
    |
    | Here you can define all available feature flags for the application.
    | Each feature has a name (display), description, and default value.
    |
    | NOTE: DockaBase is single-tenant - features are global per instance.
    |
    */

    'definitions' => [
        'dynamic-api' => [
            'name' => 'Dynamic REST API',
            'description' => 'API REST auto-gerada a partir do schema do banco de dados',
            'default' => false,
        ],
        'realtime' => [
            'name' => 'Realtime Subscriptions',
            'description' => 'Websockets com LISTEN/NOTIFY do PostgreSQL',
            'default' => false,
        ],
        'storage' => [
            'name' => 'File Storage',
            'description' => 'MinIO com buckets e políticas de acesso',
            'default' => false,
        ],
        'otp-auth' => [
            'name' => 'OTP Authentication',
            'description' => 'Login sem senha via código de única vez',
            'default' => false,
        ],
        'database-encryption' => [
            'name' => 'Database Encryption',
            'description' => 'Criptografia de dados sensíveis com pgcrypto',
            'default' => false,
        ],
        'automated-backups' => [
            'name' => 'Automated Backups',
            'description' => 'Backups automáticos programados com retenção',
            'default' => false,
        ],
        'rls' => [
            'name' => 'Row Level Security',
            'description' => 'Isolamento de dados por linha no PostgreSQL',
            'default' => false,
        ],
        'advanced-rbac' => [
            'name' => 'Advanced RBAC',
            'description' => 'Controle de acesso granular com permissões customizadas',
            'default' => false,
        ],
    ],
];
