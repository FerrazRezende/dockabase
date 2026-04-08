<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ProfilePictureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProfilePictureServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfilePictureService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');

        $this->service = new ProfilePictureService();
        $this->user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
    }

    public function test_upload_generates_correct_filename(): void
    {
        $photo = UploadedFile::fake()->image('photo.jpg');
        $path = $this->service->upload($this->user, $photo);

        $this->assertStringContainsString('joaosilva', $path);
        $this->assertStringEndsWith('.jpg', $path);
    }

    public function test_upload_creates_folder_for_user_email(): void
    {
        $photo = UploadedFile::fake()->image('photo.jpg');
        $path = $this->service->upload($this->user, $photo);

        $this->assertStringContainsString('joao@example.com', $path);
        Storage::disk('minio')->assertExists($path);
    }

    public function test_delete_removes_old_photo(): void
    {
        $photo = UploadedFile::fake()->image('photo.jpg');
        $path = $this->service->upload($this->user, $photo);

        $this->service->delete($path);

        Storage::disk('minio')->assertMissing($path);
    }

    public function test_generates_path_from_user_email_and_name(): void
    {
        $path = $this->service->generatePath($this->user, 'jpg');

        $this->assertEquals('profilepic/joao@example.com/joaosilva.jpg', $path);
    }

    public function test_getUrl_generates_temporary_signed_url(): void
    {
        $photo = UploadedFile::fake()->image('photo.jpg');
        $path = $this->service->upload($this->user, $photo);

        $url = $this->service->getUrl($path);

        // URL should contain the path
        $this->assertStringContainsString($path, $url);
        // URL should not be null
        $this->assertNotNull($url);
        // URL should be a valid URL string
        $this->assertIsString($url);
    }

    public function test_getUrl_returns_null_for_null_path(): void
    {
        $url = $this->service->getUrl(null);

        $this->assertNull($url);
    }
}
