<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

/**
 * Credential Policy - RBAC permissions within the credentials-manager feature.
 *
 * Feature flag access is controlled by middleware.
 * This policy handles fine-grained permissions (create, update, delete).
 */
class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        // All users with feature access can view credentials
        return true;
    }

    public function view(User $user, Credential $credential): bool
    {
        // All users with feature access can view credentials
        return true;
    }

    public function create(User $user): bool
    {
        // Only admins can create credentials
        return $user->is_admin === true;
    }

    public function update(User $user, Credential $credential): bool
    {
        // Only admins can update credentials
        return $user->is_admin === true;
    }

    public function delete(User $user, Credential $credential): bool
    {
        // Only admins can delete credentials
        return $user->is_admin === true;
    }
}
