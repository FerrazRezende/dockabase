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

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Manually add avatar column if it doesn't exist
        if (!Schema::hasColumn('users', 'avatar')) {
            Schema::table('users', function ($table) {
                $table->string('avatar')->nullable()->after('email');
            });
        }

        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        // Clear the fake storage between tests
        Storage::fake('minio');
        parent::tearDown();
    }

    public function test_user_has_avatar_attribute(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create(['avatar' => 'test-avatar.jpg']);
        // The avatar accessor returns a proxy URL (/avatars/{id}), not the raw path
        $avatarUrl = $user->avatar;
        $this->assertNotEmpty($avatarUrl);
        $this->assertStringContainsString('/avatars/', $avatarUrl);
        $this->assertStringContainsString($user->id, $avatarUrl);
    }

    public function test_avatar_defaults_to_null(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
        $this->assertNull($user->avatar);
    }

    public function test_guest_cannot_upload_profile_photo(): void
    {
        Storage::fake('minio');

        $response = $this->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_photo_validation_requires_image(): void
    {
        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => 'not-a-file',
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_photo_validation_accepts_jpg(): void
    {
        Storage::fake('minio');

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_photo_validation_accepts_png(): void
    {
        Storage::fake('minio');

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('photo.png', 150, 150),
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_photo_validation_rejects_large_files(): void
    {
        Storage::fake('minio');

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()
                    ->image('photo.jpg', 200, 200)
                    ->size(5000), // 5MB
            ])
            ->assertSessionHasErrors('photo');
    }

    public function test_authenticated_user_can_upload_photo(): void
    {
        Storage::fake('minio');

        $response = $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ]);

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertNotNull($this->user->getAttributes()['avatar']);
    }

    public function test_authenticated_user_can_delete_photo(): void
    {
        Storage::fake('minio');
        $this->user->update(['avatar' => 'profilepic/test.jpg']);

        $response = $this->actingAs($this->user)
            ->delete(route('profile.photo.destroy'));

        $response->assertRedirect();
        $this->user->refresh();
        $this->assertNull($this->user->getAttributes()['avatar']);
    }

    public function test_upload_deletes_old_photo(): void
    {
        Storage::fake('minio');
        $this->user->update(['avatar' => 'profilepic/test.jpg']);

        $oldPath = $this->user->getAttributes()['avatar'];

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('newphoto.jpg', 200, 200),
            ]);

        Storage::disk('minio')->assertMissing($oldPath);
    }

    public function test_full_profile_photo_workflow(): void
    {
        Storage::fake('minio');
        $this->user->refresh(); // Ensure fresh user instance

        // User uploads photo
        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('myphoto.jpg', 200, 200),
            ])
            ->assertSessionHasNoErrors();

        // Verify database updated
        $this->user->refresh();
        $avatarPath = $this->user->getAttributes()['avatar'];
        $this->assertNotNull($avatarPath);
        $this->assertStringContainsString('profilepic/', $avatarPath);

        // Verify file stored
        Storage::disk('minio')->assertExists($avatarPath);

        // Verify proxy URL is generated (contains /avatars/ path)
        $avatarUrl = $this->user->avatar;
        $this->assertStringContainsString('/avatars/', $avatarUrl);

        // User uploads different photo (old one should be deleted)
        $oldPath = $avatarPath;

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('newphoto.png', 300, 300),
            ])
            ->assertSessionHasNoErrors();

        // Old file deleted
        Storage::disk('minio')->assertMissing($oldPath);

        // New file exists
        $this->user->refresh();
        $newPath = $this->user->getAttributes()['avatar'];
        Storage::disk('minio')->assertExists($newPath);

        // User deletes photo
        $this->actingAs($this->user)
            ->delete(route('profile.photo.destroy'))
            ->assertRedirect();

        // File deleted and database cleared
        Storage::disk('minio')->assertMissing($newPath);
        $this->user->refresh();
        $this->assertNull($this->user->getAttributes()['avatar']);
    }

    public function test_avatar_url_is_proxy_url(): void
    {
        Storage::fake('minio');

        $this->actingAs($this->user)
            ->post(route('profile.photo.store'), [
                'photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
            ]);

        $this->user->refresh();
        $avatarUrl = $this->user->avatar;

        // The avatar accessor returns a proxy URL
        $this->assertIsString($avatarUrl);
        $this->assertStringContainsString('/avatars/', $avatarUrl);
    }
}
