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
    | NOTE: DockaBase supports multiple databases per instance.
    | Features are global with per-database overrides.
    |
    */

    'definitions' => [
        // Phase 2: Database Creator
        'database-creator' => [
            'name' => 'Database Creator',
            'description' => 'Interface para criar e gerenciar múltiplos databases PostgreSQL',
            'default' => false,
        ],

        // Phase 3: Schema Builder
        'schema-builder' => [
            'name' => 'Schema Builder',
            'description' => 'Interface visual para criar tabelas, colunas e relacionamentos',
            'default' => false,
        ],

        // Phase 4: Table Manager
        'table-manager' => [
            'name' => 'Table Manager',
            'description' => 'CRUD de dados com interface tipo planilha',
            'default' => false,
        ],

        // Phase 5: Dynamic REST API
        'dynamic-api' => [
            'name' => 'Dynamic REST API',
            'description' => 'API REST auto-gerada a partir do schema do banco de dados',
            'default' => false,
        ],

        // Phase 6: OTP Authentication
        'otp-auth' => [
            'name' => 'OTP Authentication',
            'description' => 'Login sem senha via código de única vez',
            'default' => false,
        ],

        // Phase 7: Realtime
        'realtime' => [
            'name' => 'Realtime Subscriptions',
            'description' => 'Websockets com LISTEN/NOTIFY do PostgreSQL',
            'default' => false,
        ],

        // Phase 8: Storage
        'storage' => [
            'name' => 'File Storage',
            'description' => 'MinIO com buckets e políticas de acesso',
            'default' => false,
        ],

        // Future features
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
