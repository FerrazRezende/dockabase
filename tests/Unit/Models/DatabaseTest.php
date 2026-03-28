<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Credential;
use App\Models\Database;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_database(): void
    {
        $database = Database::factory()->create([
            'name' => 'dev',
            'display_name' => 'Development',
            'database_name' => 'dockabase_dev',
        ]);

        $this->assertEquals('dev', $database->name);
        $this->assertEquals('Development', $database->display_name);
        $this->assertTrue($database->is_active);
        $this->assertMatchesRegularExpression('/^[0-9a-zA-Z]{27}$/', $database->id);
    }

    public function test_has_many_credentials(): void
    {
        $database = Database::factory()->create();
        $credential = Credential::factory()->create();

        $database->credentials()->attach($credential);

        $this->assertCount(1, $database->credentials);
        $this->assertTrue($database->credentials->first()->is($credential));
    }

    public function test_scope_active_filters_inactive(): void
    {
        Database::factory()->create(['name' => 'active', 'is_active' => true]);
        Database::factory()->create(['name' => 'inactive', 'is_active' => false]);

        $active = Database::active()->get();

        $this->assertCount(1, $active);
        $this->assertEquals('active', $active->first()->name);
    }

    public function test_settings_is_cast_to_array(): void
    {
        $database = Database::factory()->create([
            'settings' => ['features' => ['realtime' => false]],
        ]);

        $this->assertIsArray($database->settings);
        $this->assertFalse($database->settings['features']['realtime']);
    }

    public function test_id_is_27_char_ksuid(): void
    {
        $database = Database::factory()->create();

        $this->assertEquals(27, strlen($database->id));
    }
}
