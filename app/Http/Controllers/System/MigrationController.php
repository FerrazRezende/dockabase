<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Requests\Migration\CreateMigrationRequest;
use App\Http\Resources\System\MigrationResource;
use App\Models\Database;
use App\Models\SystemMigration;
use App\Services\MigrationExecutorService;
use App\Services\MigrationGeneratorService;
use App\Services\SchemaIntrospectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{DB, Gate};

class MigrationController
{
    public function __construct(
        private SchemaIntrospectionService $introspectionService,
        private MigrationGeneratorService $migrationGeneratorService,
        private MigrationExecutorService $migrationExecutorService,
    ) {}

    public function index(Database $database)
    {
        Gate::authorize('view', $database);

        $migrations = $database->migrations()->orderBy('batch')->orderBy('created_at')->get();

        return MigrationResource::collection($migrations);
    }

    public function store(Database $database, CreateMigrationRequest $request)
    {
        Gate::authorize('update', $database);

        // TODO: Implement migration creation
    }

    public function rollback(Database $database, SystemMigration $migration)
    {
        Gate::authorize('update', $database);

        // TODO: Implement rollback
    }

    public function showSql(Database $database, SystemMigration $migration): JsonResponse
    {
        Gate::authorize('view', $database);

        return response()->json([
            'sql_up' => $migration->sql_up,
            'sql_down' => $migration->sql_down,
        ]);
    }
}
