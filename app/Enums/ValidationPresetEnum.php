<?php

declare(strict_types=1);

namespace App\Enums;

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
            self::REGEX => 'Regex',
            self::UNIQUE => 'Unique',
            self::EXISTS => 'Exists',
            self::EMAIL => 'Email',
            self::URL => 'URL',
            self::UUID => 'UUID',
            self::DATE => 'Date',
            self::BOOLEAN => 'Boolean',
            self::IN_LIST => 'In List',
            self::ALPHA => 'Alpha',
            self::ALPHA_NUM => 'Alpha Num',
            self::ALPHA_DASH => 'Alpha Dash',
        };
    }

    public function hasValue(): bool
    {
        return in_array($this, [
            self::MIN_LENGTH, self::MAX_LENGTH,
            self::MIN_VALUE, self::MAX_VALUE,
            self::REGEX, self::EXISTS, self::IN_LIST,
        ], true);
    }

    public function applicableCategories(): array
    {
        $all = ['text', 'numeric', 'boolean', 'datetime', 'uuid', 'json', 'array', 'network'];

        return match ($this) {
            self::REQUIRED, self::NUMERIC => $all,
            self::MIN_LENGTH, self::MAX_LENGTH, self::REGEX,
            self::EMAIL, self::URL, self::UUID,
            self::ALPHA, self::ALPHA_NUM, self::ALPHA_DASH,
            self::IN_LIST => ['text'],
            self::MIN_VALUE, self::MAX_VALUE, self::INTEGER => ['numeric'],
            self::BOOLEAN => ['boolean'],
            self::DATE => ['datetime'],
            self::UNIQUE => $all,
            self::EXISTS => $all,
        };
    }

    public function toLaravelRule(?string $value = null, ?string $table = null, ?string $column = null): string
    {
        return match ($this) {
            self::REQUIRED => 'required',
            self::MIN_LENGTH => "min:{$value}",
            self::MAX_LENGTH => "max:{$value}",
            self::MIN_VALUE => "min:{$value}",
            self::MAX_VALUE => "max:{$value}",
            self::INTEGER => 'integer',
            self::NUMERIC => 'numeric',
            self::REGEX => "regex:{$value}",
            self::UNIQUE => "unique:{$table},{$column}",
            self::EXISTS => "exists:{$value}",
            self::EMAIL => 'email',
            self::URL => 'url',
            self::UUID => 'uuid',
            self::DATE => 'date',
            self::BOOLEAN => 'boolean',
            self::IN_LIST => "in:{$value}",
            self::ALPHA => 'alpha',
            self::ALPHA_NUM => 'alpha_num',
            self::ALPHA_DASH => 'alpha_dash',
        };
    }
}
