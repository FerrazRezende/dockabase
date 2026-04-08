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

    public function test_user_has_avatar_attribute(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create(['avatar' => 'test-avatar.jpg']);
        $this->assertEquals('test-avatar.jpg', $user->avatar);
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
}
