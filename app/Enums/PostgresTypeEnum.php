<?php

declare(strict_types=1);

namespace App\Enums;

enum PostgresTypeEnum: string
{
    // Numeric
    case INTEGER = 'integer';
    case BIGINT = 'bigint';
    case DECIMAL = 'decimal';
    case REAL = 'real';

    // Text
    case VARCHAR = 'varchar';
    case TEXT = 'text';
    case CHAR = 'char';

    // Boolean
    case BOOLEAN = 'boolean';

    // Datetime
    case TIMESTAMP = 'timestamp';
    case DATE = 'date';
    case TIME = 'time';

    // UUID
    case UUID = 'uuid';

    // JSON
    case JSONB = 'jsonb';
    case JSON = 'json';

    // Array
    case TEXT_ARRAY = 'text_array';
    case INTEGER_ARRAY = 'integer_array';
    case UUID_ARRAY = 'uuid_array';

    // Network
    case INET = 'inet';
    case CIDR = 'cidr';

    public function label(): string
    {
        return match ($this) {
            self::INTEGER => 'Integer',
            self::BIGINT => 'Bigint',
            self::DECIMAL => 'Decimal',
            self::REAL => 'Real',
            self::VARCHAR => 'Varchar',
            self::TEXT => 'Text',
            self::CHAR => 'Char',
            self::BOOLEAN => 'Boolean',
            self::TIMESTAMP => 'Timestamp',
            self::DATE => 'Date',
            self::TIME => 'Time',
            self::UUID => 'UUID',
            self::JSONB => 'JSONB',
            self::JSON => 'JSON',
            self::TEXT_ARRAY => 'Text Array',
            self::INTEGER_ARRAY => 'Integer Array',
            self::UUID_ARRAY => 'UUID Array',
            self::INET => 'INET',
            self::CIDR => 'CIDR',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::INTEGER, self::BIGINT, self::DECIMAL, self::REAL => 'numeric',
            self::VARCHAR, self::TEXT, self::CHAR => 'text',
            self::BOOLEAN => 'boolean',
            self::TIMESTAMP, self::DATE, self::TIME => 'datetime',
            self::UUID => 'uuid',
            self::JSONB, self::JSON => 'json',
            self::TEXT_ARRAY, self::INTEGER_ARRAY, self::UUID_ARRAY => 'array',
            self::INET, self::CIDR => 'network',
        };
    }

    public function hasLength(): bool
    {
        return in_array($this, [self::VARCHAR, self::CHAR], true);
    }

    public function toSqlDefinition(?int $length = null): string
    {
        return match ($this) {
            self::VARCHAR => $length ? "varchar({$length})" : 'varchar(255)',
            self::CHAR => $length ? "char({$length})" : 'char(1)',
            self::TEXT_ARRAY => 'text[]',
            self::INTEGER_ARRAY => 'integer[]',
            self::UUID_ARRAY => 'uuid[]',
            default => $this->value,
        };
    }
}
