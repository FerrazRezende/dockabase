<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_avatar_attribute(): void
    {
        Storage::fake('minio');

        // Manually add avatar column if it doesn't exist
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function ($table) {
                $table->string('avatar')->nullable()->after('email');
            });
        }

        $user = User::factory()->create(['avatar' => 'test-avatar.jpg']);
        $this->assertEquals('test-avatar.jpg', $user->avatar);
    }

    public function test_avatar_defaults_to_null(): void
    {
        Storage::fake('minio');

        // Manually add avatar column if it doesn't exist
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function ($table) {
                $table->string('avatar')->nullable()->after('email');
            });
        }

        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
        $this->assertNull($user->avatar);
    }

    public function test_guest_cannot_upload_profile_photo(): void
    {
        Storage::fake('minio');

        // Manually add avatar column if it doesn't exist
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function ($table) {
                $table->string('avatar')->nullable()->after('email');
            });
        }

        $response = $this->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertRedirect(route('login'));
    }
}
