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
            'description' => 'Interface para criar e gerenciar databases PostgreSQL',
            'implemented_at' => '2026-03-15',
        ],
        'credentials-manager' => [
            'name' => 'Credentials Manager',
            'description' => 'Gerenciamento de credenciais de acesso ao sistema',
            'implemented_at' => '2026-03-20',
        ],
    ],
];
