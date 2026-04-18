<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PostgresTypeEnum;
use Tests\TestCase;

class PostgresTypeEnumTest extends TestCase
{
    public function test_has_all_types(): void
    {
        $cases = PostgresTypeEnum::cases();
        $this->assertCount(19, $cases);
    }

    public function test_label_returns_human_readable_string(): void
    {
        $this->assertEquals('Integer', PostgresTypeEnum::INTEGER->label());
        $this->assertEquals('Varchar', PostgresTypeEnum::VARCHAR->label());
        $this->assertEquals('JSONB', PostgresTypeEnum::JSONB->label());
    }

    public function test_category_returns_correct_category(): void
    {
        $this->assertEquals('numeric', PostgresTypeEnum::INTEGER->category());
        $this->assertEquals('text', PostgresTypeEnum::VARCHAR->category());
        $this->assertEquals('json', PostgresTypeEnum::JSONB->category());
        $this->assertEquals('array', PostgresTypeEnum::TEXT_ARRAY->category());
    }

    public function test_has_length_returns_true_for_varchar_and_char(): void
    {
        $this->assertTrue(PostgresTypeEnum::VARCHAR->hasLength());
        $this->assertTrue(PostgresTypeEnum::CHAR->hasLength());
        $this->assertFalse(PostgresTypeEnum::INTEGER->hasLength());
        $this->assertFalse(PostgresTypeEnum::TEXT->hasLength());
    }

    public function test_to_sql_definition_returns_correct_sql(): void
    {
        $this->assertEquals('integer', PostgresTypeEnum::INTEGER->toSqlDefinition());
        $this->assertEquals('varchar(255)', PostgresTypeEnum::VARCHAR->toSqlDefinition(255));
        $this->assertEquals('text', PostgresTypeEnum::TEXT->toSqlDefinition());
        $this->assertEquals('uuid', PostgresTypeEnum::UUID->toSqlDefinition());
        $this->assertEquals('jsonb', PostgresTypeEnum::JSONB->toSqlDefinition());
        $this->assertEquals('text[]', PostgresTypeEnum::TEXT_ARRAY->toSqlDefinition());
    }
}
