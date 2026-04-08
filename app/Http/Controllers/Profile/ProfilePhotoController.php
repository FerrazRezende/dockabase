<?php

declare(strict_types=1);

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfilePhotoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfilePhotoController extends Controller
{
    public function store(UpdateProfilePhotoRequest $request): RedirectResponse
    {
        // TODO: Implement photo upload logic in Task 10
        return redirect()->back()->with('status', 'photo-upload-not-implemented');
    }
}
