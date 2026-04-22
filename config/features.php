<?php

return [
    /*
    |--------------------------------------------------------------------------
    | First Deploy Date
    |--------------------------------------------------------------------------
    |
    | Data do primeiro deploy em produção. Features com implemented_at
    | anterior ou igual a esta data são ativadas por padrão em prod.
    | Em dev/local, todas as features implementadas são ativadas.
    |
    */
    'first_deploy_date' => env('FEATURES_FIRST_DEPLOY_DATE'),

    'definitions' => [
        'database-creator' => [
            'name' => 'Database Creator',
            'description' => 'Interface for creating and managing PostgreSQL databases',
            'implemented_at' => '2026-03-15',
        ],
        'credentials-manager' => [
            'name' => 'Credentials Manager',
            'description' => 'Management of system access credentials',
            'implemented_at' => '2026-03-20',
        ],
        'schema-builder' => [
            'name' => 'Schema Builder',
            'description' => 'Visual interface for creating tables and columns',
            'implemented_at' => '2026-04-18',
        ],
        'table-manager' => [
            'name' => 'Table Manager',
            'description' => 'Spreadsheet-like CRUD data interface',
            'implemented_at' => null,
        ],
        'dynamic-api' => [
            'name' => 'Dynamic REST API',
            'description' => 'Auto-generated REST API from database schema',
            'implemented_at' => null,
        ],
        'realtime' => [
            'name' => 'Realtime',
            'description' => 'WebSockets with PostgreSQL LISTEN/NOTIFY',
            'implemented_at' => null,
        ],
        'storage' => [
            'name' => 'Storage',
            'description' => 'MinIO S3-compatible storage with access policies',
            'implemented_at' => null,
        ],
        'otp-auth' => [
            'name' => 'OTP Authentication',
            'description' => 'Passwordless authentication via OTP codes',
            'implemented_at' => null,
        ],
        'database-encryption' => [
            'name' => 'Database Encryption',
            'description' => 'Column-level encryption with pgcrypto',
            'implemented_at' => null,
        ],
        'automated-backups' => [
            'name' => 'Automated Backups',
            'description' => 'Automatic daily backups with configurable retention',
            'implemented_at' => null,
        ],
        'rls' => [
            'name' => 'Row Level Security',
            'description' => 'PostgreSQL Row Level Security policies',
            'implemented_at' => null,
        ],
        'advanced-rbac' => [
            'name' => 'Advanced RBAC',
            'description' => 'Advanced role-based access control',
            'implemented_at' => null,
        ],
    ],
];
