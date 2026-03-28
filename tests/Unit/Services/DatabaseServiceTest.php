<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use App\Services\DatabaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private DatabaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DatabaseService::class);
    }

    public function test_create_database(): void
    {
        $result = $this->service->create([
            'name' => 'dev',
            'display_name' => 'Development',
            'database_name' => 'dockabase_dev',
        ]);

        $this->assertEquals('dev', $result->name);
        $this->assertEquals('Development', $result->display_name);
        $this->assertDatabaseHas('databases', ['name' => 'dev']);
    }

    public function test_attach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $this->service->attachCredential($database, $credential);

        $this->assertTrue($database->credentials->contains($credential));
    }

    public function test_detach_credential(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();
        $database->credentials()->attach($credential);

        $this->service->detachCredential($database, $credential);

        $this->assertFalse($database->fresh()->credentials->contains($credential));
    }

    public function test_get_databases_for_user(): void
    {
        $user = User::factory()->create();
        $credential = Credential::factory()->create();
        $database = Database::factory()->create();

        $credential->users()->attach($user);
        $database->credentials()->attach($credential);

        $result = $this->service->getDatabasesForUser($user);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->is($database));
    }

    public function test_delete_database(): void
    {
        $database = Database::factory()->create();

        $this->service->delete($database->id);

        $this->assertDatabaseMissing('databases', ['id' => $database->id]);
    }
}
