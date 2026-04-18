<?php

declare(strict_types=1);

namespace App\Http\Controllers\App;

use App\Http\Requests\SchemaBuilder\{CreateTableRequest, TableDataRequest};
use App\Http\Resources\App\{ColumnResource, SchemaResource, TableDataResource};
use App\Models\Database;
use App\Services\{MigrationExecutorService, MigrationGeneratorService, SchemaBuilderService, SchemaIntrospectionService};
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\{DB, Gate};

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

        return DB::transaction(function () use ($database, $migrationDef, $metadata, $request) {
            $migration = $database->migrations()->create([
                'batch' => 1,
                'name' => 'Create table ' . $request->input('name'),
                'operation' => $migrationDef['operation'],
                'table_name' => $request->input('name'),
                'schema_name' => $request->input('schema', 'public'),
                'sql_up' => $migrationDef['sql_up'],
                'sql_down' => $migrationDef['sql_down'],
                'status' => 'executed',
                'executed_at' => now(),
            ]);

            $this->migrationExecutorService->execute($database, $migrationDef['sql_up']);

            $database->tableMetadata()->create([
                'schema_name' => $request->input('schema', 'public'),
                'table_name' => $request->input('name'),
                'columns' => $metadata['columns'],
                'validations' => $metadata['validations'],
            ]);

            return redirect()->back()->with('toast', ['message' => __('Table created successfully')]);
        });
    }

    public function destroy(Database $database, string $schema, string $table)
    {
        Gate::authorize('delete', $database);

        // TODO: Implement drop table
    }
}
