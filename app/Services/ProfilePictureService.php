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
            ['visibility' => 'private']
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

        return self::BUCKET . '/' . $user->email . '/' . $filename;
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
