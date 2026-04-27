<?php

declare(strict_types=1);

namespace App\Services;

class MigrationGeneratorService
{
    public function generateCreateTable(string $schema, string $table, array $columns): array
    {
        $columnDefs = [];

        foreach ($columns as $column) {
            $columnDef = "\"{$column['name']}\" {$column['type_definition']}";

            if (! ($column['nullable'] ?? false)) {
                $columnDef .= ' NOT NULL';
            }

            if ($column['default_value'] ?? null) {
                $columnDef .= ' DEFAULT ' . $column['default_value'];
            }

            if ($column['is_primary_key'] ?? false) {
                $columnDef .= ' PRIMARY KEY';
            }

            $columnDefs[] = $columnDef;
        }

        $sqlUp = 'CREATE TABLE "' . $schema . '"."' . $table . '" (' . implode(', ', $columnDefs) . ');';
        $sqlDown = 'DROP TABLE IF EXISTS "' . $schema . '"."' . $table . '";';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'add_column',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateDropTable(string $schema, string $table, array $existingColumns): array
    {
        $sqlUp = 'DROP TABLE IF EXISTS "' . $schema . '"."' . $table . '";';

        $columnDefs = [];
        foreach ($existingColumns as $col) {
            $nullable = ($col['nullable'] ?? false) ? '' : ' NOT NULL';
            $columnDefs[] = "\"{$col['name']}\" {$col['type']}{$nullable}";
        }

        $sqlDown = 'CREATE TABLE "' . $schema . '"."' . $table . '" (' . implode(', ', $columnDefs) . ');';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'drop_table',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateAddColumn(string $schema, string $table, array $column): array
    {
        $nullable = ($column['nullable'] ?? false) ? '' : ' NOT NULL';
        $default = ($column['default_value'] ?? null) ? ' DEFAULT ' . $column['default_value'] : '';

        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" ADD COLUMN "' . $column['name'] . '" ' . $column['type_definition'] . $nullable . $default . ';';
        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" DROP COLUMN ' . $column['name'] . ';';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'add_column',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateDropColumn(string $schema, string $table, string $column, string $type): array
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" DROP COLUMN ' . $column . ';';
        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" ADD COLUMN "' . $column . '" ' . $type . ';';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'drop_column',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateAlterColumnType(string $schema, string $table, string $column, string $fromType, string $toType): array
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" ALTER COLUMN "' . $column . '" TYPE ' . $toType . ';';
        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" ALTER COLUMN "' . $column . '" TYPE ' . $fromType . ';';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'alter_column_type',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateRenameColumn(string $schema, string $table, string $from, string $to): array
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $table . '" RENAME COLUMN "' . $from . '" TO "' . $to . '";';
        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $table . '" RENAME COLUMN "' . $to . '" TO "' . $from . '";';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'rename_column',
            'table_name' => $table,
            'schema_name' => $schema,
        ];
    }

    public function generateRenameTable(string $schema, string $fromTable, string $toTable): array
    {
        $sqlUp = 'ALTER TABLE "' . $schema . '"."' . $fromTable . '" RENAME TO "' . $toTable . '";';
        $sqlDown = 'ALTER TABLE "' . $schema . '"."' . $toTable . '" RENAME TO "' . $fromTable . '";';

        return [
            'sql_up' => $sqlUp,
            'sql_down' => $sqlDown,
            'operation' => 'rename_table',
            'table_name' => $fromTable,
            'schema_name' => $schema,
        ];
    }
}
