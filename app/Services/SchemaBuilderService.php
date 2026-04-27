<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PostgresTypeEnum;

class SchemaBuilderService
{
    private const RESERVED_PREFIXES = ['pg_', 'system_'];

    public function validateTableName(string $name): void
    {
        $this->validateIdentifier($name, 'table name');
    }

    public function validateColumnName(string $name): void
    {
        $this->validateIdentifier($name, 'column name');
    }

    public function buildColumnDefinitions(array $columns): array
    {
        $result = [];

        foreach ($columns as $column) {
            $this->validateColumnName($column['name']);

            $typeEnum = PostgresTypeEnum::from($column['type']);
            $typeDefinition = $typeEnum->toSqlDefinition($column['length'] ?? null);

            $result[] = [
                'name' => $column['name'],
                'type' => $column['type'],
                'type_definition' => $typeDefinition,
                'nullable' => (bool) ($column['nullable'] ?? false),
                'default_value' => $column['default_value'] ?? null,
                'is_primary_key' => (bool) ($column['is_primary_key'] ?? false),
                'foreign_key' => $column['foreign_key'] ?? null,
            ];
        }

        return $result;
    }

    public function prepareTableMetadata(array $columns, ?array $validations = null): array
    {
        return [
            'columns' => $this->buildColumnDefinitions($columns),
            'validations' => $validations ?? [],
        ];
    }

    private function validateIdentifier(string $name, string $label): void
    {
        if (strlen($name) > 63) {
            throw new \InvalidArgumentException("{$label} too long (max 63 characters)");
        }

        if (! preg_match('/^[a-z_][a-z0-9_]{0,62}$/', $name)) {
            throw new \InvalidArgumentException("Invalid {$label}: must start with letter or underscore, contain only lowercase letters, numbers, and underscores");
        }

        foreach (self::RESERVED_PREFIXES as $prefix) {
            if (str_starts_with($name, $prefix)) {
                throw new \InvalidArgumentException("{$label} cannot start with reserved prefix '{$prefix}'");
            }
        }
    }
}
