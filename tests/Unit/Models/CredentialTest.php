<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_credential(): void
    {
        $credential = Credential::factory()->create([
            'name' => 'Dev Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
        ]);

        $this->assertEquals('Dev Team', $credential->name);
        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $credential->permission);
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{27}$/', $credential->id);
    }

    public function test_has_many_users(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $credential->users()->attach($user);

        $this->assertCount(1, $credential->users);
        $this->assertTrue($credential->users->first()->is($user));
    }

    public function test_has_many_databases(): void
    {
        $credential = Credential::factory()->create();
        $database = Database::factory()->create();

        $credential->databases()->attach($database);

        $this->assertCount(1, $credential->databases);
        $this->assertTrue($credential->databases->first()->is($database));
    }

    public function test_permission_is_cast_to_enum(): void
    {
        $credential = Credential::factory()->create([
            'permission' => CredentialPermissionEnum::Read,
        ]);

        $this->assertInstanceOf(CredentialPermissionEnum::class, $credential->permission);
    }

    public function test_has_read_permission(): void
    {
        $readCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::Read]);
        $writeCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::Write]);
        $rwCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::ReadWrite]);

        $this->assertTrue($readCredential->hasReadPermission());
        $this->assertFalse($writeCredential->hasReadPermission());
        $this->assertTrue($rwCredential->hasReadPermission());
    }

    public function test_has_write_permission(): void
    {
        $readCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::Read]);
        $writeCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::Write]);
        $rwCredential = Credential::factory()->create(['permission' => CredentialPermissionEnum::ReadWrite]);

        $this->assertFalse($readCredential->hasWritePermission());
        $this->assertTrue($writeCredential->hasWritePermission());
        $this->assertTrue($rwCredential->hasWritePermission());
    }

    public function test_id_is_27_char_ksuid(): void
    {
        $credential = Credential::factory()->create();

        $this->assertEquals(27, strlen($credential->id));
    }
}
