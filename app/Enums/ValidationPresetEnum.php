<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\PostgresTypeEnum;

enum ValidationPresetEnum: string
{
    case REQUIRED = 'required';
    case MIN_LENGTH = 'min_length';
    case MAX_LENGTH = 'max_length';
    case MIN_VALUE = 'min_value';
    case MAX_VALUE = 'max_value';
    case INTEGER = 'integer';
    case NUMERIC = 'numeric';
    case REGEX = 'regex';
    case UNIQUE = 'unique';
    case EXISTS = 'exists';
    case EMAIL = 'email';
    case URL = 'url';
    case UUID = 'uuid';
    case DATE = 'date';
    case BOOLEAN = 'boolean';
    case IN_LIST = 'in_list';
    case ALPHA = 'alpha';
    case ALPHA_NUM = 'alpha_num';
    case ALPHA_DASH = 'alpha_dash';

    public function label(): string
    {
        return match ($this) {
            self::REQUIRED => 'Required',
            self::MIN_LENGTH => 'Min Length',
            self::MAX_LENGTH => 'Max Length',
            self::MIN_VALUE => 'Min Value',
            self::MAX_VALUE => 'Max Value',
            self::INTEGER => 'Integer',
            self::NUMERIC => 'Numeric',
            self::REGEX => 'Regex Pattern',
            self::UNIQUE => 'Unique in Table',
            self::EXISTS => 'Exists in Table',
            self::EMAIL => 'Must be Email',
            self::URL => 'Must be URL',
            self::UUID => 'Must be UUID',
            self::DATE => 'Must be Date',
            self::BOOLEAN => 'Must be Boolean',
            self::IN_LIST => 'In List',
            self::ALPHA => 'Only Letters',
            self::ALPHA_NUM => 'Letters + Numbers',
            self::ALPHA_DASH => 'Letters, Numbers, Dash',
        };
    }

    public function toLaravelRule(mixed $value = null): string
    {
        return match ($this) {
            self::REQUIRED => 'required',
            self::MIN_LENGTH => $value !== null ? "min:{$value}" : 'min',
            self::MAX_LENGTH => $value !== null ? "max:{$value}" : 'max',
            self::MIN_VALUE => $value !== null ? "min:{$value}" : 'min',
            self::MAX_VALUE => $value !== null ? "max:{$value}" : 'max',
            self::INTEGER => 'integer',
            self::NUMERIC => 'numeric',
            self::REGEX => $value !== null ? "regex:{$value}" : 'regex',
            self::UNIQUE => $value !== null ? "unique:{$value}" : 'unique',
            self::EXISTS => $value !== null ? "exists:{$value}" : 'exists',
            self::EMAIL => 'email',
            self::URL => 'url',
            self::UUID => 'uuid',
            self::DATE => 'date',
            self::BOOLEAN => 'boolean',
            self::IN_LIST => $value !== null ? "in:{$value}" : 'in',
            self::ALPHA => 'alpha',
            self::ALPHA_NUM => 'alpha_num',
            self::ALPHA_DASH => 'alpha_dash',
        };
    }

    public function applicableTypes(): array
    {
        return match ($this) {
            self::REQUIRED, self::NUMERIC, self::UNIQUE, self::EXISTS, self::UUID, self::BOOLEAN => PostgresTypeEnum::cases(),
            self::MIN_LENGTH, self::MAX_LENGTH, self::REGEX, self::ALPHA, self::ALPHA_NUM, self::ALPHA_DASH, self::EMAIL, self::URL => [
                PostgresTypeEnum::VARCHAR, PostgresTypeEnum::TEXT, PostgresTypeEnum::CHAR,
            ],
            self::MIN_VALUE, self::MAX_VALUE, self::INTEGER => [
                PostgresTypeEnum::INTEGER, PostgresTypeEnum::BIGINT, PostgresTypeEnum::DECIMAL, PostgresTypeEnum::REAL,
            ],
            self::DATE => [
                PostgresTypeEnum::TIMESTAMP, PostgresTypeEnum::DATE, PostgresTypeEnum::TIME,
            ],
            self::IN_LIST => [
                PostgresTypeEnum::VARCHAR, PostgresTypeEnum::TEXT, PostgresTypeEnum::CHAR,
            ],
        };
    }
}
