<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

/**
 * Database Policy - RBAC permissions within the database-creator feature.
 *
 * Feature flag access is controlled by middleware.
 * This policy handles fine-grained permissions (create, update, delete).
 */
class DatabasePolicy
{
    public function viewAny(User $user): bool
    {
        // All users with feature access can view databases
        return true;
    }

    public function view(User $user, Database $database): bool
    {
        // All users with feature access can view databases
        return true;
    }

    public function create(User $user): bool
    {
        // Only admins can create databases
        return $user->is_admin === true;
    }

    public function update(User $user, Database $database): bool
    {
        // Only admins can update databases
        return $user->is_admin === true;
    }

    public function delete(User $user, Database $database): bool
    {
        // Only admins can delete databases
        return $user->is_admin === true;
    }
}
