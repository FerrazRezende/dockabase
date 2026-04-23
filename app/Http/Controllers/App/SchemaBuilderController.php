<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Requests\SchemaBuilder\{CreateTableRequest, TableDataRequest};
use App\Http\Resources\App\{ColumnResource, SchemaResource, TableDataResource};
use App\Jobs\{CreateSchemaJob, CreateTableJob};
use App\Models\Database;
use App\Models\DatabaseTableMetadata;
use App\Services\{MigrationExecutorService, MigrationGeneratorService, SchemaBuilderService, SchemaIntrospectionService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchemaBuilderController
{
    public function __construct(
        private SchemaIntrospectionService $introspectionService,
        private SchemaBuilderService $schemaBuilderService,
        private MigrationGeneratorService $migrationGeneratorService,
        private MigrationExecutorService $migrationExecutorService,
    ) {}

    public function index(Database $database): SchemaResource
    {
        Gate::authorize('view', $database);

        $schemas = [];

        foreach ($this->introspectionService->getSchemas($database) as $schemaName) {
            $tables = [];

            foreach ($this->introspectionService->getTables($database, $schemaName) as $tableName) {
                $columns = $this->introspectionService->getColumns($database, $schemaName, $tableName);
                $rowCount = $this->introspectionService->getTableRowCount($database, $schemaName, $tableName);

                $tables[] = [
                    'name' => $tableName,
                    'schema' => $schemaName,
                    'rowCount' => $rowCount,
                    'columns' => $columns,
                ];
            }

            $schemas[] = [
                'name' => $schemaName,
                'tables' => $tables,
            ];
        }

        return new SchemaResource($schemas);
    }

    public function tableData(Database $database, string $schema, string $table, TableDataRequest $request): TableDataResource
    {
        Gate::authorize('view', $database);

        $data = $this->introspectionService->getTableData(
            $database,
            $schema,
            $table,
            $request->integer('page', 1),
            $request->integer('per_page', 50),
            $request->input('search'),
            $request->input('sort_by'),
            $request->input('sort_dir', 'asc'),
        );

        return new TableDataResource(array_merge($data, [
            'table' => $table,
            'schema' => $schema,
        ]));
    }

    public function columns(Database $database, string $schema, string $table)
    {
        Gate::authorize('view', $database);

        $columns = $this->introspectionService->getColumns($database, $schema, $table);

        return ColumnResource::collection($columns);
    }

    public function storeSchema(Database $database, \Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('update', $database);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:63', 'regex:/^[a-z][a-z0-9_]*$/'],
        ]);

        CreateSchemaJob::dispatch($database, $validated['name']);

        return response()->json([
            'message' => __('Schema creation request sent successfully'),
        ]);
    }

    public function store(Database $database, CreateTableRequest $request): RedirectResponse
    {
        Gate::authorize('create', $database);

        $this->schemaBuilderService->validateTableName($request->input('name'));

        $metadata = $this->schemaBuilderService->prepareTableMetadata(
            $request->input('columns'),
            $request->input('validations'),
        );

        $migrationDef = $this->migrationGeneratorService->generateCreateTable(
            $request->input('schema', 'public'),
            $request->input('name'),
            $metadata['columns'],
        );

        CreateTableJob::dispatch(
            $database,
            $request->input('schema', 'public'),
            $request->input('name'),
            $metadata['columns'],
            $metadata['validations'],
            $request->input('messages'),
            $migrationDef,
        );

        return redirect()->back()->with('toast', ['message' => __('Table creation request sent successfully')]);
    }

    public function destroy(Database $database, string $schema, string $table)
    {
        Gate::authorize('delete', $database);

        // TODO: Implement drop table
    }

    public function settings(Database $database, string $schema, string $table): Response
    {
        Gate::authorize('view', $database);

        $columns = $this->introspectionService->getColumns($database, $schema, $table);
        $metadata = DatabaseTableMetadata::where('database_id', $database->id)
            ->where('schema_name', $schema)
            ->where('table_name', $table)
            ->first();

        return Inertia::render('App/Tables/Settings', [
            'database' => $database,
            'schema' => $schema,
            'table' => $table,
            'columns' => $columns,
            'validations' => $metadata?->validations ?? [],
            'messages' => $metadata?->messages ?? [],
        ]);
    }

    public function updateSettings(Database $database, string $schema, string $table, \Illuminate\Http\Request $request): RedirectResponse
    {
        Gate::authorize('update', $database);

        $validated = $request->validate([
            'validations' => ['nullable', 'array'],
            'messages' => ['nullable', 'array'],
        ]);

        DatabaseTableMetadata::updateOrCreate(
            [
                'database_id' => $database->id,
                'schema_name' => $schema,
                'table_name' => $table,
            ],
            [
                'validations' => $validated['validations'] ?? [],
                'messages' => $validated['messages'] ?? [],
            ],
        );

        return redirect()->back()->with('toast', ['message' => __('Table settings updated successfully')]);
    }
}
