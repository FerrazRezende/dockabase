<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;

class SchemaIntrospectionService
{
    private const EXCLUDED_SCHEMAS = ['pg_catalog', 'information_schema', 'pg_toast'];

    /**
     * Quote a PostgreSQL identifier (schema, table, column name) safely.
     * Doubles any embedded double-quote to prevent injection.
     */
    private function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    /**
     * Validate that an identifier contains only safe characters.
     * PostgreSQL identifiers: letters, digits, underscores. Must start with a letter or underscore.
     */
    private function validateIdentifier(string $identifier): void
    {
        if (! preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,62}$/', $identifier)) {
            throw new \InvalidArgumentException("Invalid identifier: {$identifier}");
        }
    }

    public function getSchemas(Database $database): array
    {
        $connection = $this->getConnection($database);

        $excluded = implode(',', array_map(fn ($s) => '?' , self::EXCLUDED_SCHEMAS));

        $rows = $connection->select(
            "SELECT schema_name FROM information_schema.schemata
             WHERE schema_name NOT IN ({$excluded})
             ORDER BY schema_name",
            self::EXCLUDED_SCHEMAS
        );

        return array_map(fn ($row) => $row->schema_name, $rows);
    }

    public function getTables(Database $database, string $schema): array
    {
        $this->validateIdentifier($schema);
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
        $this->validateIdentifier($schema);
        $this->validateIdentifier($table);
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
        $this->validateIdentifier($schema);
        $this->validateIdentifier($table);
        $connection = $this->getConnection($database);

        $qualifiedTable = $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier($table);
        $offset = ($page - 1) * $perPage;
        $perPage = max(1, min(500, $perPage));

        // Count total rows
        $bindings = [];
        $countSql = "SELECT COUNT(*) as total FROM {$qualifiedTable}";

        if ($search) {
            $countSql .= " WHERE CAST(* AS TEXT) ILIKE ?";
            $bindings[] = '%' . $search . '%';
        }

        $totalRows = (int) $connection->selectOne($countSql, $bindings)->total;

        // Fetch rows
        $dataBindings = $search ? ['%' . $search . '%'] : [];
        $sql = "SELECT * FROM {$qualifiedTable}";

        if ($search) {
            $sql .= " WHERE CAST(* AS TEXT) ILIKE ?";
        }

        if ($sortBy) {
            $this->validateIdentifier($sortBy);
            $direction = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= ' ORDER BY ' . $this->quoteIdentifier($sortBy) . ' ' . $direction;
        }

        $sql .= ' LIMIT ? OFFSET ?';
        $dataBindings[] = $perPage;
        $dataBindings[] = $offset;

        $rows = $connection->select($sql, $dataBindings);
        $columns = ! empty($rows) ? array_keys((array) $rows[0]) : [];

        return [
            'rows' => array_map(fn ($row) => (array) $row, $rows),
            'totalRows' => $totalRows,
            'columns' => $columns,
        ];
    }

    public function getTableRowCount(Database $database, string $schema, string $table): int
    {
        $this->validateIdentifier($schema);
        $this->validateIdentifier($table);
        $connection = $this->getConnection($database);

        $qualifiedTable = $this->quoteIdentifier($schema) . '.' . $this->quoteIdentifier($table);
        $result = $connection->selectOne("SELECT COUNT(*) as count FROM {$qualifiedTable}");

        return (int) $result->count;
    }

    public function createSchema(Database $database, string $schema): void
    {
        $this->validateIdentifier($schema);
        $connection = $this->getConnection($database);

        $connection->statement('CREATE SCHEMA IF NOT EXISTS ' . $this->quoteIdentifier($schema));
    }

    private function getConnection(Database $database): ConnectionInterface
    {
        $connectionName = "tenant_{$database->id}";
        $default = config('database.connections.pgsql');

        config(["database.connections.{$connectionName}" => [
            'driver' => 'pgsql',
            'host' => $default['host'],
            'port' => $default['port'],
            'database' => $database->database_name,
            'username' => $default['username'],
            'password' => $default['password'],
        ]]);

        return DB::connection($connectionName);
    }
}
