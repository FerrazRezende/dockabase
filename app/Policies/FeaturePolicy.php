<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FeaturePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view features.
     * Only God Admin (is_admin = true) can manage features.
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the user can view a specific feature.
     */
    public function view(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the user can activate features.
     */
    public function activate(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the user can deactivate features.
     */
    public function deactivate(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the user can update feature settings.
     */
    public function update(User $user): bool
    {
        return $user->is_admin === true;
    }

    /**
     * Determine whether the user can manage feature users.
     */
    public function manageUsers(User $user): bool
    {
        return $user->is_admin === true;
    }
}
