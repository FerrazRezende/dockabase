<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Services\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialServiceTest extends TestCase
{
    use RefreshDatabase;

    private CredentialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CredentialService::class);
    }

    public function test_create_credential(): void
    {
        $user = User::factory()->create();

        $result = $this->service->create([
            'name' => 'Dev Team',
            'permission' => CredentialPermissionEnum::ReadWrite,
            'user_ids' => [$user->id],
        ]);

        $this->assertEquals('Dev Team', $result->name);
        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $result->permission);
        $this->assertTrue($result->users->contains($user));
    }

    public function test_attach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();

        $this->service->attachUser($credential, (string) $user->id);

        $this->assertTrue($credential->fresh()->users->contains($user));
    }

    public function test_detach_user(): void
    {
        $credential = Credential::factory()->create();
        $user = User::factory()->create();
        $credential->users()->attach($user);

        $this->service->detachUser($credential, (string) $user->id);

        $this->assertFalse($credential->fresh()->users->contains($user));
    }

    public function test_get_user_permission_for_database(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create([
            'permission' => CredentialPermissionEnum::ReadWrite,
        ]);
        $database = Database::factory()->create();

        $credential->users()->attach($user);
        $database->credentials()->attach($credential);

        $result = $this->service->getUserPermissionForDatabase($user, $database->name);

        $this->assertEquals(CredentialPermissionEnum::ReadWrite, $result);
    }

    public function test_get_user_permission_for_database_returns_null_if_no_access(): void
    {
        $user = User::factory()->create();
        $database = Database::factory()->create();

        $result = $this->service->getUserPermissionForDatabase($user, $database->name);

        $this->assertNull($result);
    }

    public function test_delete_credential(): void
    {
        $credential = Credential::factory()->create();

        $this->service->delete($credential->id);

        $this->assertDatabaseMissing('credentials', ['id' => $credential->id]);
    }
}
