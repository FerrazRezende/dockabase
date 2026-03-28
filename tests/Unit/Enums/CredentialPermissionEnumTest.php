<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CredentialPermissionEnum;
use Tests\TestCase;

class CredentialPermissionEnumTest extends TestCase
{
    public function test_has_read_permission(): void
    {
        $this->assertEquals('read', CredentialPermissionEnum::Read->value);
    }

    public function test_has_write_permission(): void
    {
        $this->assertEquals('write', CredentialPermissionEnum::Write->value);
    }

    public function test_has_read_write_permission(): void
    {
        $this->assertEquals('read-write', CredentialPermissionEnum::ReadWrite->value);
    }

    public function test_all_permissions_defined(): void
    {
        $this->assertCount(3, CredentialPermissionEnum::cases());
    }

    public function test_label_returns_human_readable(): void
    {
        $this->assertEquals('Read Only', CredentialPermissionEnum::Read->label());
        $this->assertEquals('Write Only', CredentialPermissionEnum::Write->label());
        $this->assertEquals('Read & Write', CredentialPermissionEnum::ReadWrite->label());
    }
}
