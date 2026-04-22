<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

class DatabasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermission('databases.view');
    }

    public function view(User $user, Database $database): bool
    {
        if (! $user->checkPermission('databases.view')) {
            return false;
        }

        return $this->canAccess($user, $database);
    }

    public function viewSchema(User $user, Database $database): bool
    {
        if (! $user->checkPermission('databases.view')) {
            return false;
        }

        return $this->canAccess($user, $database);
    }

    public function create(User $user): bool
    {
        return $user->checkPermission('databases.create');
    }

    public function update(User $user, Database $database): bool
    {
        if (! $user->checkPermission('databases.update')) {
            return false;
        }

        return $this->canAccess($user, $database);
    }

    public function delete(User $user, Database $database): bool
    {
        if (! $user->checkPermission('databases.delete')) {
            return false;
        }

        return $this->canAccess($user, $database);
    }

    private function canAccess(User $user, Database $database): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($database->created_by === $user->id) {
            return true;
        }

        return $database->credentials()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->exists();
    }
}
