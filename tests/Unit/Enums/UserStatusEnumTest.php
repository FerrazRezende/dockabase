<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserStatusEnum;
use Tests\TestCase;

class UserStatusEnumTest extends TestCase
{
    public function test_online_case_exists(): void
    {
        $status = UserStatusEnum::ONLINE;
        $this->assertEquals('online', $status->value);
    }

    public function test_away_case_exists(): void
    {
        $status = UserStatusEnum::AWAY;
        $this->assertEquals('away', $status->value);
    }

    public function test_busy_case_exists(): void
    {
        $status = UserStatusEnum::BUSY;
        $this->assertEquals('busy', $status->value);
    }

    public function test_offline_case_exists(): void
    {
        $status = UserStatusEnum::OFFLINE;
        $this->assertEquals('offline', $status->value);
    }

    public function test_label_returns_portuguese_translation(): void
    {
        $this->assertEquals('Online', UserStatusEnum::ONLINE->label());
        $this->assertEquals('Ausente', UserStatusEnum::AWAY->label());
        $this->assertEquals('Ocupado', UserStatusEnum::BUSY->label());
        $this->assertEquals('Offline', UserStatusEnum::OFFLINE->label());
    }

    public function test_color_returns_hex_value(): void
    {
        $this->assertEquals('#22c55e', UserStatusEnum::ONLINE->color());
        $this->assertEquals('#eab308', UserStatusEnum::AWAY->color());
        $this->assertEquals('#ef4444', UserStatusEnum::BUSY->color());
        $this->assertEquals('#6b7280', UserStatusEnum::OFFLINE->color());
    }

    public function test_all_statuses_returns_array(): void
    {
        $statuses = UserStatusEnum::all();
        $this->assertCount(4, $statuses);
        $this->assertArrayHasKey('online', $statuses);
        $this->assertArrayHasKey('away', $statuses);
        $this->assertArrayHasKey('busy', $statuses);
        $this->assertArrayHasKey('offline', $statuses);
    }
}
