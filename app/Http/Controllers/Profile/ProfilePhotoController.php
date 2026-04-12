<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePhotoRequest;
use App\Services\ProfilePictureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfilePhotoController extends Controller
{
    public function __construct(
        private ProfilePictureService $profilePictureService
    ) {
    }

    public function store(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Delete old photo if exists (use raw attribute, not accessor which returns signed URL)
        $oldAvatarPath = $user->getAttributes()['avatar'] ?? null;
        if ($oldAvatarPath) {
            $this->profilePictureService->delete($oldAvatarPath);
        }

        // Upload new photo
        $photoPath = $this->profilePictureService->upload($user, $request->file('photo'));

        // Update user's avatar
        $user->update(['avatar' => $photoPath]);

        return redirect()
            ->back()
            ->with('toast', [
                'message' => __('Profile photo updated successfully'),
                'type' => 'success',
            ]);
    }

    public function destroy(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Delete photo from storage (use raw attribute, not accessor which returns signed URL)
        $avatarPath = $user->getAttributes()['avatar'] ?? null;
        if ($avatarPath) {
            $this->profilePictureService->delete($avatarPath);
        }

        // Set user's avatar to null
        $user->update(['avatar' => null]);

        return redirect()
            ->back()
            ->with('toast', [
                'message' => __('Profile photo removed successfully'),
                'type' => 'success',
            ]);
    }
}
