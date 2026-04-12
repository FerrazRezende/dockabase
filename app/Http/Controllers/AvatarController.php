<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

final class AvatarController extends Controller
{
    /**
     * Serve a user's avatar image from MinIO.
     */
    public function show(Request $request, string $userId): Response
    {
        $user = User::findOrFail($userId);

        $rawAvatar = $user->getRawOriginal('avatar');

        if (!$rawAvatar) {
            abort(404);
        }

        $disk = Storage::disk('minio');

        if (!$disk->exists($rawAvatar)) {
            abort(404);
        }

        $data = $disk->get($rawAvatar);
        $mimeType = $disk->mimeType($rawAvatar);

        return new Response($data, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
