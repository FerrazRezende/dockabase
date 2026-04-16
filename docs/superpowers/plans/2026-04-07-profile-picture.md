# Profile Picture Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Enable users to upload, store, and manage profile pictures using MinIO S3-compatible storage with private bucket security

**Architecture:**
- MinIO as S3-compatible storage backend with **private** `profilepic` bucket
- User photos stored in folders named after their email: `profilepic/user@email.com/filename.png`
- Filenames generated from user's full name (concatenated, no spaces): `joaosilva.png`
- **Security:** Private bucket with temporary signed URLs for viewing (expires in 1 hour)
- Laravel's S3 filesystem driver with path-style endpoint for MinIO compatibility
- ProfilePictureService handles upload/delete with signed URL generation
- UI: Split card design - avatar card on top (clickable to open modal), language card below

**Tech Stack:**
- Laravel 13 Storage (S3 driver)
- MinIO (already configured in docker-compose.yml)
- Vue 3 + TypeScript + shadcn-vue components
- Intervention Image for image optimization

---

## File Structure

```
database/migrations/
  2026_04_07_000001_add_avatar_to_users_table.php (NEW)

app/
  Services/
    ProfilePictureService.php (NEW)
  Http/
    Controllers/Profile/
      ProfilePhotoController.php (NEW)
    Requests/
      UpdateProfilePhotoRequest.php (NEW)

app/Console/Commands/
  InitMinioBucketsCommand.php (NEW)

resources/js/Pages/Profile/Partials/
  ProfilePhotoCard.vue (NEW - compact avatar display card)
  ProfilePhotoDialog.vue (NEW - modal with full image + upload form)

resources/js/components/ui/
  avatar/ (ALREADY EXISTS)
  button/ (ALREADY EXISTS)
  dialog/ (ALREADY EXISTS)

tests/Feature/Profile/
  ProfilePhotoTest.php (NEW)

lang/
  pt.json (MODIFY - add translations)
  en.json (MODIFY - add translations)
  es.json (MODIFY - add translations)

config/
  filesystems.php (MODIFY - add minio disk)
```

---

## Task 1: Configure MinIO Disk

**Files:**
- Modify: `config/filesystems.php`

- [ ] **Step 1: Add MinIO disk configuration**

Add the `minio` disk to the disks array in `config/filesystems.php` after the `s3` disk:

```php
'minio' => [
    'driver' => 's3',
    'key' => env('MINIO_ROOT_USER'),
    'secret' => env('MINIO_ROOT_PASSWORD'),
    'region' => env('MINIO_REGION', 'us-east-1'),
    'bucket' => env('MINIO_BUCKET', 'dockabase'),
    'endpoint' => env('MINIO_ENDPOINT'),
    'use_path_style_endpoint' => true,
    'url' => env('MINIO_PUBLIC_URL'),
    'throw' => false,
    'report' => false,
],
```

- [ ] **Step 2: Run tests to verify config loads**

Run: `php artisan config:clear && php artisan config:cache`
Expected: No errors, config cached successfully

- [ ] **Step 3: Commit**

```bash
git add config/filesystems.php
git commit -m "feat: add minio disk configuration to filesystem"
```

---

## Task 2: Create MinIO Bucket Initialization Command

**Files:**
- Create: `app/Console/Commands/InitMinioBucketsCommand.php`

- [ ] **Step 1: Create the command file**

Create `app/Console/Commands/InitMinioBucketsCommand.php`:

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class InitMinioBucketsCommand extends Command
{
    protected $signature = 'minio:init-buckets';
    protected $description = 'Create required MinIO buckets for profile pictures';

    public function handle(): int
    {
        $disk = 'minio';
        $buckets = ['profilepic'];

        $this->info('Initializing MinIO buckets...');

        foreach ($buckets as $bucket) {
            $this->info("Checking bucket: {$bucket}");

            // Check if bucket exists by trying to list files
            try {
                Storage::disk($disk)->listContents('/');
                $this->info("  - Bucket '{$bucket}' already accessible");
            } catch (\Exception $e) {
                $this->warn("  - Bucket '{$bucket}' not found. Please create it manually in MinIO console.");
                $this->warn("    URL: http://localhost:9001");
                $this->warn("    Access Key: " . env('MINIO_ROOT_USER'));
            }
        }

        $this->info('MinIO buckets check complete!');
        return Command::SUCCESS;
    }
}
```

- [ ] **Step 2: Run the command to verify**

Run: `php artisan minio:init-buckets`
Expected: Command runs without fatal errors (will warn if bucket doesn't exist yet)

- [ ] **Step 3: Commit**

```bash
git add app/Console/Commands/InitMinioBucketsCommand.php
git commit -m "feat: add minio buckets initialization command"
```

---

## Task 3: Add Avatar Column to Users Table

**Files:**
- Create: `database/migrations/2026_04_07_000001_add_avatar_to_users_table.php`
- Test: `tests/Feature/Profile/ProfilePhotoTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Profile/ProfilePhotoTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('minio');

        $this->user = User::factory()->create([
            'name' => 'João Silva',
            'email' => 'joao@example.com',
        ]);
    }

    public function test_user_has_avatar_attribute(): void
    {
        $this->assertArrayHasKey('avatar', $this->user->getAttributes());
    }

    public function test_avatar_defaults_to_null(): void
    {
        $this->assertNull($this->user->avatar);
    }

    public function test_guest_cannot_upload_profile_photo(): void
    {
        $response = $this->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertRedirect(route('login'));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php`
Expected: FAIL with "Column not found: 1054 Unknown column 'avatar' in 'field list'"

- [ ] **Step 3: Create migration**

Create `database/migrations/2026_04_07_000001_add_avatar_to_users_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar');
        });
    }
};
```

- [ ] **Step 4: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php --filter test_user_has_avatar_attribute`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_04_07_000001_add_avatar_to_users_table.php tests/Feature/Profile/ProfilePhotoTest.php
git commit -m "feat: add avatar column to users table"
```

---

## Task 4: Create ProfilePictureService

**Files:**
- Create: `app/Services/ProfilePictureService.php`
- Test: `tests/Unit/Services/ProfilePictureServiceTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Unit/Services/ProfilePictureServiceTest.php`:

```php
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

        // Signed URL contains expiration signature
        $this->assertStringContainsString('X-Amz', $url);
        $this->assertStringContainsString('Expires', $url);
        $this->assertStringContainsString('Signature', $url);
    }

    public function test_getUrl_returns_null_for_null_path(): void
    {
        $url = $this->service->getUrl(null);

        $this->assertNull($url);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/ProfilePictureServiceTest.php`
Expected: FAIL with "Class App\Services\ProfilePictureService not found"

- [ ] **Step 3: Create ProfilePictureService**

Create `app/Services/ProfilePictureService.php`:

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePictureService
{
    private const string BUCKET = 'profilepic';

    public function upload(User $user, UploadedFile $photo): string
    {
        $path = $this->generatePath($user, $photo->extension());

        Storage::disk('minio')->putFileAs(
            dirname($path),
            $photo,
            basename($path),
            ['visibility' => 'public']
        );

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('minio')->exists($path)) {
            Storage::disk('minio')->delete($path);
        }
    }

    public function getUrl(?string $path, int $expirationMinutes = 60): ?string
    {
        if (!$path) {
            return null;
        }

        // Generate temporary signed URL for private bucket access
        return Storage::disk('minio')->temporaryUrl(
            $path,
            now()->addMinutes($expirationMinutes)
        );
    }

    public function generatePath(User $user, string $extension): string
    {
        $sanitizedName = $this->sanitizeName($user->name);
        $filename = $sanitizedName . '.' . $extension;

        return self::BUUCKET . '/' . $user->email . '/' . $filename;
    }

    private function sanitizeName(string $name): string
    {
        // Remove spaces, accents, and special characters
        return Str::of($name)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->toString();
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/ProfilePictureServiceTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProfilePictureService.php tests/Unit/Services/ProfilePictureServiceTest.php
git commit -m "feat: add profile picture service with upload and delete"
```

---

## Task 5: Create UpdateProfilePhotoRequest FormRequest

**Files:**
- Create: `app/Http/Requests/UpdateProfilePhotoRequest.php`
- Test: `tests/Feature/Profile/ProfilePhotoTest.php` (modify)

- [ ] **Step 1: Add validation test to existing test file**

Add to `tests/Feature/Profile/ProfilePhotoTest.php`:

```php
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
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ])
        ->assertSessionHasNoErrors();
}

public function test_photo_validation_accepts_png(): void
{
    Storage::fake('minio');

    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.png'),
        ])
        ->assertSessionHasNoErrors();
}

public function test_photo_validation_rejects_large_files(): void
{
    Storage::fake('minio');

    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()
                ->image('photo.jpg')
                ->size(5000), // 5MB
        ])
        ->assertSessionHasErrors('photo');
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php --filter test_photo_validation`
Expected: FAIL with validation not working yet

- [ ] **Step 3: Create FormRequest**

Create `app/Http/Requests/UpdateProfilePhotoRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'max:2048', // 2MB
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => __('The photo field is required.'),
            'photo.image' => __('The file must be an image.'),
            'photo.mimes' => __('The photo must be a file of type: jpg, jpeg, png.'),
            'photo.max' => __('The photo may not be greater than 2MB.'),
            'photo.dimensions' => __('The photo must be between 100x100 and 2000x2000 pixels.'),
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php --filter test_photo_validation`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Http/Requests/UpdateProfilePhotoRequest.php tests/Feature/Profile/ProfilePhotoTest.php
git commit -m "feat: add profile photo validation request"
```

---

## Task 6: Create ProfilePhotoController

**Files:**
- Create: `app/Http/Controllers/Profile/ProfilePhotoController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Profile/ProfilePhotoTest.php` (modify)

- [ ] **Step 1: Add controller test**

Add to `tests/Feature/Profile/ProfilePhotoTest.php`:

```php
public function test_authenticated_user_can_upload_photo(): void
{
    Storage::fake('minio');

    $response = $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

    $response->assertRedirect();
    $this->user->refresh();
    $this->assertNotNull($this->user->avatar);
}

public function test_authenticated_user_can_delete_photo(): void
{
    Storage::fake('minio');
    $this->user->update(['avatar' => 'profilepic/test.jpg']);

    $response = $this->actingAs($this->user)
        ->delete(route('profile.photo.destroy'));

    $response->assertRedirect();
    $this->user->refresh();
    $this->assertNull($this->user->avatar);
}

public function test_upload_deletes_old_photo(): void
{
    Storage::fake('minio');
    $this->user->update(['avatar' => 'profilepic/test.jpg']);

    $oldPath = $this->user->avatar;

    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('newphoto.jpg'),
        ]);

    Storage::disk('minio')->assertMissing($oldPath);
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php --filter test_authenticated_user_can_upload`
Expected: FAIL with "Route [profile.photo.store] not defined"

- [ ] **Step 3: Create controller**

Create `app/Http/Controllers/Profile/ProfilePhotoController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Services\ProfilePictureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ProfilePhotoController extends Controller
{
    public function __construct(
        private ProfilePictureService $service
    ) {}

    public function store(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Delete old photo if exists
        if ($user->avatar) {
            $this->service->delete($user->avatar);
        }

        // Upload new photo
        $path = $this->service->upload($user, $request->file('photo'));

        // Update user
        $user->update(['avatar' => $path]);

        return Redirect::route('profile.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => __('Profile photo updated successfully'),
            ]);
    }

    public function destroy(\Illuminate\Http\Request $request): RedirectResponse
    {
        $user = $request->user();

        // Delete photo from storage
        $this->service->delete($user->avatar);

        // Clear avatar from user
        $user->update(['avatar' => null]);

        return Redirect::route('profile.edit')
            ->with('toast', [
                'type' => 'success',
                'message' => __('Profile photo removed successfully'),
            ]);
    }
}
```

- [ ] **Step 4: Add routes**

Add to `routes/web.php` inside the profile routes group (find the existing profile routes):

```php
Route::middleware(['auth'])->group(function () {
    // ... existing profile routes ...

    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');
});
```

Also add the import at the top:
```php
use App\Http\Controllers\Profile\ProfilePhotoController;
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php --filter test_authenticated_user`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Profile/ProfilePhotoController.php routes/web.php tests/Feature/Profile/ProfilePhotoTest.php
git commit -m "feat: add profile photo controller with routes"
```

---

## Task 7: Create Profile Photo UI Components (Split Card Design)

**UI Design:**
- Avatar Card (top): Shows user avatar or placeholder, clickable to open modal
- Photo Dialog (modal): Shows full-size image with upload/delete options
- Locale Card (bottom): Existing language selector (unchanged)

**Files:**
- Create: `resources/js/Pages/Profile/Partials/ProfilePhotoCard.vue` (compact display)
- Create: `resources/js/Pages/Profile/Partials/ProfilePhotoDialog.vue` (modal with upload)
- Modify: `resources/js/Pages/Profile/Edit.vue` (update layout)

- [ ] **Step 1: Create ProfilePhotoCard component**

Create `resources/js/Pages/Profile/Partials/ProfilePhotoCard.vue`:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Camera } from 'lucide-vue-next';
import ProfilePhotoDialog from './ProfilePhotoDialog.vue';
import { __ } from '@/composables/useLang';

const page = usePage();
const user = computed(() => page.props.auth.user as { name: string; email: string; avatar?: string });
const isDialogOpen = ref(false);

const initials = computed(() => {
    return user.value.name
        .split(' ')
        .map((n: string) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

const openDialog = () => {
    isDialogOpen.value = true;
};
</script>

<template>
    <!-- Avatar Card - Click to open dialog -->
    <div
        class="bg-card shadow-sm rounded-lg border border-border p-6 cursor-pointer transition-colors hover:bg-accent/50"
        @click="openDialog"
    >
        <div class="flex flex-col items-center gap-4">
            <!-- Avatar -->
            <Avatar class="h-32 w-32 ring-4 ring-border hover:ring-primary/50 transition-all">
                <AvatarImage :src="user.avatar" />
                <AvatarFallback class="bg-primary text-primary-foreground text-4xl">
                    {{ initials }}
                </AvatarFallback>
            </Avatar>

            <!-- Camera Icon & Label -->
            <div class="flex flex-col items-center gap-1">
                <div class="flex items-center gap-2 text-sm font-medium text-foreground">
                    <Camera class="h-4 w-4" />
                    <span>{{ __('Change photo') }}</span>
                </div>
                <p class="text-xs text-muted-foreground text-center">
                    {{ __('Click to upload or change your profile photo') }}
                </p>
            </div>
        </div>
    </div>

    <!-- Dialog for viewing/uploading -->
    <ProfilePhotoDialog v-model:open="isDialogOpen" />
</template>
```

- [ ] **Step 2: Create ProfilePhotoDialog component**

Create `resources/js/Pages/Profile/Partials/ProfilePhotoDialog.vue`:

```vue
<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Upload, Trash2, Loader2, X } from 'lucide-vue-next';
import { __ } from '@/composables/useLang';

interface Props {
    open: boolean;
}

interface Emits {
    (e: 'update:open', value: boolean): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const page = usePage();
const user = computed(() => page.props.auth.user as { name: string; email: string; avatar?: string });

const preview = ref<string | null>(user.value.avatar || null);
const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const isUploading = ref(false);

const uploadForm = useForm({
    photo: null as File | null,
});

const deleteForm = useForm({});

const isOpen = computed({
    get: () => props.open,
    set: (value) => emit('update:open', value),
});

const initials = computed(() => {
    return user.value.name
        .split(' ')
        .map((n: string) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
});

// Update preview when user avatar changes
watch(() => user.value.avatar, (newAvatar) => {
    preview.value = newAvatar || null;
});

const closeDialog = () => {
    isOpen.value = false;
};

const selectFile = () => {
    fileInput.value?.click();
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        processFile(file);
    }
};

const processFile = (file: File) => {
    // Validate file type
    if (!file.type.startsWith('image/')) {
        return;
    }

    // Validate file size (2MB)
    if (file.size > 2 * 1024 * 1024) {
        return;
    }

    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        preview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);

    // Upload
    isUploading.value = true;
    uploadForm.photo = file;
    uploadForm.post(route('profile.photo.store'), {
        onSuccess: () => {
            uploadForm.reset();
            isUploading.value = false;
            // Reload page to get new signed URL
            window.location.reload();
        },
        onError: () => {
            isUploading.value = false;
        },
    });
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;

    const file = event.dataTransfer?.files[0];
    if (file) {
        processFile(file);
    }
};

const handleDragOver = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const deletePhoto = () => {
    if (confirm(__('Are you sure you want to remove your profile photo?'))) {
        deleteForm.delete(route('profile.photo.destroy'), {
            onSuccess: () => {
                preview.value = null;
                closeDialog();
                // Reload page to update UI
                window.location.reload();
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ __('Profile Photo') }}</DialogTitle>
                <DialogDescription>
                    {{ __('View, upload, or change your profile photo.') }}
                </DialogDescription>
                <Button
                    variant="ghost"
                    size="icon"
                    class="absolute right-4 top-4"
                    @click="closeDialog"
                >
                    <X class="h-4 w-4" />
                </Button>
            </DialogHeader>

            <div class="space-y-6">
                <!-- Full-size Avatar Preview -->
                <div class="flex justify-center">
                    <Avatar class="h-64 w-64 ring-4 ring-border">
                        <AvatarImage :src="preview" />
                        <AvatarFallback class="bg-primary text-primary-foreground text-6xl">
                            {{ initials }}
                        </AvatarFallback>
                    </Avatar>
                </div>

                <!-- Upload Area -->
                <div v-if="!isUploading">
                    <div
                        class="relative border-2 border-dashed rounded-lg p-6 text-center transition-colors cursor-pointer"
                        :class="[
                            isDragging
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-primary/50 hover:bg-accent/5',
                        ]"
                        @click="selectFile"
                        @drop="handleDrop"
                        @dragover="handleDragOver"
                        @dragleave="handleDragLeave"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            class="hidden"
                            accept="image/jpeg,image/png,image/jpg"
                            @change="handleFileChange"
                        />

                        <Upload class="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
                        <p class="text-sm font-medium text-foreground">
                            {{ __('Click to upload or drag and drop') }}
                        </p>
                        <p class="text-xs text-muted-foreground mt-1">
                            {{ __('JPG, PNG up to 2MB') }}
                        </p>
                    </div>
                </div>

                <!-- Upload Progress -->
                <div v-else class="flex flex-col items-center gap-2">
                    <Loader2 class="h-8 w-8 text-muted-foreground animate-spin" />
                    <p class="text-sm text-muted-foreground">{{ __('Uploading...') }}</p>
                </div>

                <!-- Delete Button -->
                <Button
                    v-if="user.avatar && !isUploading"
                    type="button"
                    variant="destructive"
                    class="w-full gap-2"
                    :disabled="deleteForm.processing"
                    @click="deletePhoto"
                >
                    <Trash2 v-if="!deleteForm.processing" class="h-4 w-4" />
                    <Loader2 v-else class="h-4 w-4 animate-spin" />
                    {{ __('Remove photo') }}
                </Button>

                <!-- Validation Errors -->
                <Alert v-if="uploadForm.errors.photo" variant="destructive">
                    <AlertDescription>
                        {{ uploadForm.errors.photo }}
                    </AlertDescription>
                </Alert>
            </div>
        </DialogContent>
    </Dialog>
</template>
```

- [ ] **Step 3: Update Profile Edit page layout**

Modify `resources/js/Pages/Profile/Edit.vue`:

```vue
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LocaleForm from './Partials/LocaleForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import ProfilePhotoCard from './Partials/ProfilePhotoCard.vue'; // NEW
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-foreground">
                Profile
            </h2>
        </template>

        <div class="p-6">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-6">
                    <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-xl"
                        />
                    </div>

                    <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                        <UpdatePasswordForm class="max-w-xl" />
                    </div>
                </div>

                <!-- Right Column: Split into Photo Card (top) and Locale Card (bottom) -->
                <div class="space-y-6">
                    <!-- Avatar Card -->
                    <ProfilePhotoCard />

                    <!-- Language Card -->
                    <div class="bg-card shadow-sm rounded-lg border border-border p-6">
                        <LocaleForm class="max-w-xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 4: Check Dialog components exist**

Verify the Dialog components from shadcn-vue exist:
- `resources/js/components/ui/dialog/Dialog.vue`
- `resources/js/components/ui/dialog/DialogContent.vue`
- `resources/js/components/ui/dialog/DialogHeader.vue`
- `resources/js/components/ui/dialog/DialogTitle.vue`
- `resources/js/components/ui/dialog/DialogDescription.vue`

Run: `ls resources/js/components/ui/dialog/`
Expected: All dialog component files exist

- [ ] **Step 5: Commit**

```bash
git add resources/js/Pages/Profile/Partials/ProfilePhotoCard.vue resources/js/Pages/Profile/Partials/ProfilePhotoDialog.vue resources/js/Pages/Profile/Edit.vue
git commit -m "feat: add split card design for profile photo with dialog modal"
```

---

## Task 8: Add Translations

**Files:**
- Modify: `lang/pt.json`
- Modify: `lang/en.json`
- Modify: `lang/es.json`

- [ ] **Step 1: Add Portuguese translations**

Add to `lang/pt.json` (maintain alphabetical order):

```json
"Profile Photo": "Foto de Perfil",
"Profile photo updated successfully": "Foto de perfil atualizada com sucesso",
"Profile photo removed successfully": "Foto de perfil removida com sucesso",
"Upload a profile photo to personalize your account.": "Envie uma foto de perfil para personalizar sua conta.",
"Click to upload or drag and drop": "Clique para enviar ou arraste e solte",
"JPG, PNG up to 2MB": "JPG, PNG até 2MB",
"Uploading...": "Enviando...",
"Remove photo": "Remover foto",
"Are you sure you want to remove your profile photo?": "Tem certeza que deseja remover sua foto de perfil?",
"The photo field is required.": "O campo foto é obrigatório.",
"The file must be an image.": "O arquivo deve ser uma imagem.",
"The photo must be a file of type: jpg, jpeg, png.": "A foto deve ser um arquivo do tipo: jpg, jpeg, png.",
"The photo may not be greater than 2MB.": "A foto não pode ser maior que 2MB.",
"The photo must be between 100x100 and 2000x2000 pixels.": "A foto deve ter entre 100x100 e 2000x2000 pixels.",
"Change photo": "Alterar foto",
"Click to upload or change your profile photo": "Clique para enviar ou alterar sua foto de perfil",
"View, upload, or change your profile photo.": "Visualize, envie ou altere sua foto de perfil."
```

- [ ] **Step 2: Add English translations**

Add to `lang/en.json` (maintain alphabetical order):

```json
"Profile Photo": "Profile Photo",
"Profile photo updated successfully": "Profile photo updated successfully",
"Profile photo removed successfully": "Profile photo removed successfully",
"Upload a profile photo to personalize your account.": "Upload a profile photo to personalize your account.",
"Click to upload or drag and drop": "Click to upload or drag and drop",
"JPG, PNG up to 2MB": "JPG, PNG up to 2MB",
"Uploading...": "Uploading...",
"Remove photo": "Remove photo",
"Are you sure you want to remove your profile photo?": "Are you sure you want to remove your profile photo?",
"The photo field is required.": "The photo field is required.",
"The file must be an image.": "The file must be an image.",
"The photo must be a file of type: jpg, jpeg, png.": "The photo must be a file of type: jpg, jpeg, png.",
"The photo may not be greater than 2MB.": "The photo may not be greater than 2MB.",
"The photo must be between 100x100 and 2000x2000 pixels.": "The photo must be between 100x100 and 2000x2000 pixels.",
"Change photo": "Change photo",
"Click to upload or change your profile photo": "Click to upload or change your profile photo",
"View, upload, or change your profile photo.": "View, upload, or change your profile photo."
```

- [ ] **Step 3: Add Spanish translations**

Add to `lang/es.json` (maintain alphabetical order):

```json
"Profile Photo": "Foto de Perfil",
"Profile photo updated successfully": "Foto de perfil actualizada con éxito",
"Profile photo removed successfully": "Foto de perfil eliminada con éxito",
"Upload a profile photo to personalize your account.": "Sube una foto de perfil para personalizar tu cuenta.",
"Click to upload or drag and drop": "Haz clic para subir o arrastra y suelta",
"JPG, PNG up to 2MB": "JPG, PNG hasta 2MB",
"Uploading...": "Subiendo...",
"Remove photo": "Eliminar foto",
"Are you sure you want to remove your profile photo?": "¿Estás seguro de que deseas eliminar tu foto de perfil?",
"The photo field is required.": "El campo foto es obligatorio.",
"The file must be an image.": "El archivo debe ser una imagen.",
"The photo must be a file of type: jpg, jpeg, png.": "La foto debe ser un archivo de tipo: jpg, jpeg, png.",
"The photo may not be greater than 2MB.": "La foto no puede ser mayor que 2MB.",
"The photo must be between 100x100 and 2000x2000 pixels.": "La foto debe tener entre 100x100 y 2000x2000 píxeles.",
"Change photo": "Cambiar foto",
"Click to upload or change your profile photo": "Haz clic para subir o cambiar tu foto de perfil",
"View, upload, or change your profile photo.": "Visualiza, sube o cambia tu foto de perfil."
```

- [ ] **Step 4: Run translation validation test**

Run: `php artisan test tests/Feature/Lang/TranslationKeysTest.php`
Expected: PASS (all keys exist in all languages)

- [ ] **Step 5: Commit**

```bash
git add lang/pt.json lang/en.json lang/es.json
git commit -m "feat: add translations for profile photo feature"
```

---

## Task 9: Update AuthenticatedLayout to Show Avatar with Signed URLs

**Files:**
- Modify: `app/Models/User.php`
- Create: `app/Http/Resources/UserResource.php`

- [ ] **Step 1: Add avatar URL accessor to User model**

Modify `app/Models/User.php` (replace existing getAvatarAttribute):

```php
public function getAvatarAttribute(): ?string
{
    $avatar = $this->attributes['avatar'] ?? null;

    if (!$avatar) {
        return null;
    }

    // Generate temporary signed URL for private bucket access
    return \Illuminate\Support\Facades\Storage::disk('minio')
        ->temporaryUrl($avatar, \Illuminate\Support\Carbon::now()->addMinutes(60));
}
```

Add the import at the top if not present:
```php
use Illuminate\Support\Facades\Storage;
```

- [ ] **Step 2: Create UserResource for API responses**

Create `app/Http/Resources/UserResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'avatar' => $this->avatar, // This will use the accessor with signed URL
            'locale' => $this->locale,
        ];
    }
}
```

- [ ] **Step 3: Update authentication middleware to use signed URLs**

Verify that `app/Http/Middleware/Authenticate.php` or any auth-related middleware returns user with avatar.

If using Inertia middleware, check `app/Http/Middleware/HandleInertiaRequests.php`:

```php
// In HandleInertiaRequests middleware's share() method:
return array_merge(parent::share($request), [
    'auth' => [
        'user' => $request->user()
            ? UserResource::make($request->user())->toArray(request())
            : null,
    ],
    // ... other shared props
]);
```

- [ ] **Step 4: Test avatar displays in sidebar**

Run: `php artisan serve` and visit the application
Expected: Avatar displays in sidebar (or fallback initials)

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php app/Http/Resources/UserResource.php
git commit -m "feat: use signed URLs for avatar in authenticated layout"
```

---

## Task 10: Create Avatar Refresh Endpoint

**Files:**
- Create: `app/Http/Controllers/Profile/ProfilePhotoRefreshController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create refresh controller**

Create `app/Http/Controllers/Profile/ProfilePhotoRefreshController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfilePhotoRefreshController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'avatar' => $user->avatar, // Generates new signed URL
        ]);
    }
}
```

- [ ] **Step 2: Add route**

Add to `routes/web.php`:

```php
Route::get('/profile/photo/refresh', ProfilePhotoRefreshController::class)
    ->name('profile.photo.refresh')
    ->middleware('auth');
```

Add import:
```php
use App\Http\Controllers\Profile\ProfilePhotoRefreshController;
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Controllers/Profile/ProfilePhotoRefreshController.php routes/web.php
git commit -m "feat: add avatar refresh endpoint for expired signed urls"
```

---

## Task 11: Manual MinIO Setup (Private Bucket)

**Security Note:** The bucket will be **private** - files accessed via signed URLs only.

**Files:**
- Documentation

- [ ] **Step 1: Create the profilepic bucket in MinIO**

1. Access MinIO console at http://localhost:9001
2. Login with credentials from `.env`:
   - Username: `MINIO_ROOT_USER` (default: `dockabase`)
   - Password: `MINIO_ROOT_PASSWORD` (default: `secret123456`)
3. Click "Buckets" → "Create Bucket"
4. Name: `profilepic`
5. Click "Create Bucket"
6. **Important:** Click on the bucket → "Access Policy" → Set to **"Private"** (NOT public)

- [ ] **Step 2: Verify bucket is private**

The bucket policy should show "Private" - only accessible via signed URLs from the application.

- [ ] **Step 3: Verify bucket creation**

Run: `php artisan minio:init-buckets`
Expected: Command reports bucket is accessible (will warn if not found)

- [ ] **Step 4: Test signed URL generation**

Run in tinker:
```bash
php artisan tinker
>>> Storage::disk('minio')->temporaryUrl('test.txt', now()->addHour());
```

Expected: Returns a URL with `?X-Amz...` signature parameters

---

## Task 12: End-to-End Testing

**Files:**
- Test: `tests/Feature/Profile/ProfilePhotoTest.php`

- [ ] **Step 1: Add full flow test**

Add to `tests/Feature/Profile/ProfilePhotoTest.php`:

```php
public function test_full_profile_photo_workflow(): void
{
    Storage::fake('minio');

    // User uploads photo
    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('myphoto.jpg'),
        ])
        ->assertRedirect(route('profile.edit'));

    // Verify database updated
    $this->user->refresh();
    $this->assertNotNull($this->user->avatar);
    $this->assertStringContainsString('profilepic/joao@example.com/joaosilva', $this->user->avatar);

    // Verify file stored
    Storage::disk('minio')->assertExists($this->user->avatar);

    // Verify signed URL is generated (contains X-Amz signature)
    $this->assertStringContainsString('X-Amz', $this->user->avatar);
    $this->assertStringContainsString('Expires', $this->user->avatar);

    // User uploads different photo (old one should be deleted)
    $oldPath = str_replace(['?X-Amz', '&'], '', explode('?', $this->user->avatar)[0]); // Get path without signature

    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('newphoto.png'),
        ])
        ->assertRedirect(route('profile.edit'));

    // Old file deleted
    Storage::disk('minio')->assertMissing($oldPath);

    // New file exists
    $this->user->refresh();
    $newPath = str_replace(['?X-Amz', '&'], '', explode('?', $this->user->avatar)[0]);
    Storage::disk('minio')->assertExists($newPath);

    // User deletes photo
    $this->actingAs($this->user)
        ->delete(route('profile.photo.destroy'))
        ->assertRedirect(route('profile.edit'));

    // File deleted and database cleared
    Storage::disk('minio')->assertMissing($newPath);
    $this->user->refresh();
    $this->assertNull($this->user->avatar);
}

public function test_avatar_url_expires(): void
{
    Storage::fake('minio');

    $this->actingAs($this->user)
        ->post(route('profile.photo.store'), [
            'photo' => UploadedFile::fake()->image('photo.jpg'),
        ]);

    $this->user->refresh();
    $avatarUrl = $this->user->avatar;

    // Parse URL to get expiration time
    parse_str(parse_url($avatarUrl, PHP_URL_QUERY), $params);
    $this->assertArrayHasKey('Expires', $params);
    $this->assertArrayHasKey('Signature', $params);
}

- [ ] **Step 2: Run all tests**

Run: `php artisan test tests/Feature/Profile/ProfilePhotoTest.php`
Expected: All tests PASS

- [ ] **Step 3: Run full test suite**

Run: `php artisan test`
Expected: All existing tests still pass (no regressions)

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Profile/ProfilePhotoTest.php
git commit -m "test: add end-to-end profile photo workflow test"
```

---

## Task 13: Manual Testing Checklist

**Files:**
- Manual verification

- [ ] **Step 1: Manual testing checklist**

Complete each item:

1. **MinIO Bucket Created (Private)**
   - [ ] Access MinIO console at http://localhost:9001
   - [ ] Verify `profilepic` bucket exists
   - [ ] Verify bucket is set to **Private** (not public)

2. **Upload Photo via Dialog**
   - [ ] Navigate to Profile page (http://localhost/profile)
   - [ ] Click on the avatar card in the right column
   - [ ] Dialog opens with current avatar (or placeholder)
   - [ ] Upload a JPG photo (drag and drop or click)
   - [ ] Verify preview shows immediately
   - [ ] Verify success message appears
   - [ ] Verify photo appears in sidebar avatar
   - [ ] Refresh page and verify photo persists

3. **File Validation**
   - [ ] Try uploading a non-image file (should error)
   - [ ] Try uploading a file > 2MB (should error)
   - [ ] Try uploading a tiny image (< 100x100) (should error)

4. **Replace Photo**
   - [ ] Open dialog and upload a new photo
   - [ ] Verify old photo is deleted from MinIO
   - [ ] Verify new photo is displayed

5. **Delete Photo**
   - [ ] Open dialog and click "Remove photo" button
   - [ ] Confirm deletion
   - [ ] Verify photo is removed from UI
   - [ ] Verify photo is deleted from MinIO
   - [ ] Verify database avatar field is null

6. **Multiple Users**
   - [ ] Create two different users with different emails
   - [ ] Upload photos for both
   - [ ] Verify each has their own photo
   - [ ] Verify files are in separate email folders

7. **Signed URL Security**
   - [ ] Inspect the avatar URL in browser DevTools
   - [ ] Verify it contains `X-Amz` signature parameters
   - [ ] Verify URL includes expiration time

- [ ] **Step 2: Document any issues found**

```bash
# If any issues found, document in a new issue ticket
```

---

## Summary

After completing all tasks, users will be able to:

1. **Upload profile photos** via click-to-upload or drag-and-drop in a dialog modal
2. **Photos stored securely** in private MinIO bucket at `profilepic/{user_email}/{sanitized_name}.ext`
3. **Access via signed URLs** - temporary URLs (1 hour expiration) with encryption
4. **Split card UI layout** - Avatar card (top, clickable) and Locale card (bottom) in right column
5. **Dialog modal** - Shows full-size image with upload/delete options
6. **Photos display in** - Sidebar avatar, profile page avatar card, and dialog
7. **Replace/delete photos** - Old photos automatically deleted when replaced
8. **File validation** - Only JPG/PNG up to 2MB, 100x100 to 2000x2000 pixels
9. **Full i18n support** - Portuguese, English, Spanish
10. **Security** - Private bucket with signed URLs (no public access)

---

**Implementation complete!** Run all tests to verify:

```bash
php artisan test
```
