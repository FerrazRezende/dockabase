<?php

declare(strict_types=1);

namespace App\Enums;

enum MigrationOperationEnum: string
{
    case ADD_COLUMN = 'add_column';
    case DROP_COLUMN = 'drop_column';
    case ALTER_COLUMN_TYPE = 'alter_column_type';
    case RENAME_COLUMN = 'rename_column';
    case ADD_CONSTRAINT = 'add_constraint';
    case DROP_CONSTRAINT = 'drop_constraint';
    case ADD_INDEX = 'add_index';
    case DROP_INDEX = 'drop_index';
    case RENAME_TABLE = 'rename_table';
    case DROP_TABLE = 'drop_table';

    public function isDestructive(): bool
    {
        return in_array($this, [self::DROP_COLUMN, self::DROP_TABLE, self::DROP_CONSTRAINT, self::DROP_INDEX], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::ADD_COLUMN => 'Add Column',
            self::DROP_COLUMN => 'Drop Column',
            self::ALTER_COLUMN_TYPE => 'Alter Column Type',
            self::RENAME_COLUMN => 'Rename Column',
            self::ADD_CONSTRAINT => 'Add Constraint',
            self::DROP_CONSTRAINT => 'Drop Constraint',
            self::ADD_INDEX => 'Add Index',
            self::DROP_INDEX => 'Drop Index',
            self::RENAME_TABLE => 'Rename Table',
            self::DROP_TABLE => 'Drop Table',
        };
    }
}
