<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\Database;

class SchemaPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermission('schemas.view');
    }

    public function view(User $user): bool
    {
        return $user->checkPermission('schemas.view');
    }

    public function create(User $user): bool
    {
        return $user->checkPermission('schemas.create');
    }

    public function update(User $user): bool
    {
        return $user->checkPermission('schemas.update');
    }

    public function delete(User $user): bool
    {
        return $user->checkPermission('schemas.delete');
    }
}
