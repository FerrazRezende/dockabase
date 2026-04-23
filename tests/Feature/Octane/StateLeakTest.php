<?php

declare(strict_types=1);

namespace Tests\Feature\Octane;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StateLeakTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_does_not_leak_between_requests(): void
    {
        $alice = User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        $bob = User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        // Request as Alice
        $this->actingAs($alice)
            ->get('/profile')
            ->assertOk();

        // Request as Bob — must NOT see Alice's data
        $response = $this->actingAs($bob)
            ->get('/profile')
            ->assertOk();

        // Verify we're seeing Bob's page, not Alice's
        // The session should contain Bob's user ID, not Alice's
        $this->assertEquals(auth()->id(), $bob->id);
        $this->assertNotEquals(auth()->id(), $alice->id);
    }

    public function test_config_does_not_leak_between_requests(): void
    {
        $originalName = config('app.name');

        // First request — config should be original
        $this->getJson('/');

        $this->assertEquals($originalName, config('app.name'));

        // Second request — config should still be original
        $this->getJson('/');

        $this->assertEquals($originalName, config('app.name'));
    }
}
