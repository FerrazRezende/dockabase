<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class SchemaIntrospectionService
{
    private const EXCLUDED_SCHEMAS = ['pg_catalog', 'information_schema', 'pg_toast'];

    public function getSchemas(Database $database): array
    {
        $connection = $this->getConnection($database);

        $rows = $connection->select(
            "SELECT schema_name FROM information_schema.schemata
             WHERE schema_name NOT IN ('" . implode("','", self::EXCLUDED_SCHEMAS) . "')
             ORDER BY schema_name"
        );

        return array_map(fn ($row) => $row->schema_name, $rows);
    }

    public function getTables(Database $database, string $schema): array
    {
        $connection = $this->getConnection($database);

        $rows = $connection->select(
            "SELECT table_name
             FROM information_schema.tables
             WHERE table_schema = ? AND table_type = 'BASE TABLE'
             ORDER BY table_name",
            [$schema]
        );

        return array_map(fn ($row) => $row->table_name, $rows);
    }

    public function getColumns(Database $database, string $schema, string $table): array
    {
        $connection = $this->getConnection($database);

        $sql = "SELECT
                    c.column_name,
                    c.data_type,
                    c.is_nullable,
                    c.column_default,
                    COALESCE(pk.column_name IS NOT NULL, FALSE) as is_primary_key,
                    COALESCE(fk.foreign_table_name IS NOT NULL, FALSE) as is_foreign_key,
                    fk.foreign_table_name,
                    fk.foreign_column_name,
                    fk.foreign_schema_name
                FROM information_schema.columns c
                LEFT JOIN (
                    SELECT ku.column_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage ku ON tc.constraint_name = ku.constraint_name
                    WHERE tc.constraint_type = 'PRIMARY KEY' AND tc.table_schema = ? AND tc.table_name = ?
                ) pk ON c.column_name = pk.column_name
                LEFT JOIN (
                    SELECT
                        ku.column_name,
                        ccu.table_name AS foreign_table_name,
                        ccu.column_name AS foreign_column_name,
                        ccu.table_schema AS foreign_schema_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage ku ON tc.constraint_name = ku.constraint_name
                    JOIN information_schema.constraint_column_usage ccu ON tc.constraint_name = ccu.constraint_name
                    WHERE tc.constraint_type = 'FOREIGN KEY' AND tc.table_schema = ? AND tc.table_name = ?
                ) fk ON c.column_name = fk.column_name
                WHERE c.table_schema = ? AND c.table_name = ?
                ORDER BY c.ordinal_position";

        $rows = $connection->select($sql, [$schema, $table, $schema, $table, $schema, $table]);

        return array_map(fn ($row) => [
            'name' => $row->column_name,
            'type' => $row->data_type,
            'nullable' => $row->is_nullable === 'YES',
            'defaultValue' => $row->column_default,
            'isPrimaryKey' => (bool) $row->is_primary_key,
            'isForeignKey' => (bool) $row->is_foreign_key,
            'foreignKey' => $row->is_foreign_key ? [
                'table' => $row->foreign_table_name,
                'column' => $row->foreign_column_name,
                'schema' => $row->foreign_schema_name,
            ] : null,
        ], $rows);
    }

    public function getTableData(
        Database $database,
        string $schema,
        string $table,
        int $page = 1,
        int $perPage = 50,
        ?string $search = null,
        ?string $sortBy = null,
        ?string $sortDir = 'ASC'
    ): array {
        $connection = $this->getConnection($database);
        $offset = ($page - 1) * $perPage;

        $totalQuery = 'SELECT COUNT(*) as total FROM "' . $schema . '"."' . $table . '"';
        if ($search) {
            $totalQuery .= " WHERE CAST(* AS TEXT) ILIKE ?";
        }

        $totalResult = $search
            ? $connection->select($totalQuery, ['%' . $search . '%'])
            : $connection->select($totalQuery);
        $totalRows = (int) $totalResult[0]->total;

        $sql = 'SELECT * FROM "' . $schema . '"."' . $table . '"';

        if ($search) {
            $sql .= " WHERE CAST(* AS TEXT) ILIKE ?";
        }

        if ($sortBy) {
            $sql .= ' ORDER BY "' . $sortBy . '" ' . ($sortDir === 'DESC' ? 'DESC' : 'ASC');
        } else {
            $sql .= ' ORDER BY 1';
        }

        $sql .= ' LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $rows = $search
            ? $connection->select($sql, ['%' . $search . '%'])
            : $connection->select($sql);

        $columns = ! empty($rows) ? array_keys((array) $rows[0]) : [];

        return [
            'rows' => array_map(fn ($row) => (array) $row, $rows),
            'totalRows' => $totalRows,
            'columns' => $columns,
        ];
    }

    public function getTableRowCount(Database $database, string $schema, string $table): int
    {
        $connection = $this->getConnection($database);

        $result = $connection->select('SELECT COUNT(*) as count FROM "' . $schema . '"."' . $table . '"');

        return (int) $result[0]->count;
    }

    private function getConnection(Database $database): ConnectionInterface
    {
        return DB::connect([
            'driver' => 'pgsql',
            'host' => $database->host,
            'port' => $database->port,
            'database' => $database->database_name,
            'username' => config('database.connections.pgsql.username'),
            'password' => config('database.connections.pgsql.password'),
        ]);
    }
}
