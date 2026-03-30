<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

class DatabasePolicy
{
    /**
     * Databases - Admin only
     * Apenas administradores podem gerenciar databases
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Database $database): bool
    {
        return $user->is_admin === true;
    }
}
