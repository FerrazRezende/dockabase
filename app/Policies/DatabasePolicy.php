<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Database;
use App\Models\User;

class DatabasePolicy
{
    /**
     * Databases - RBAC (quando implementado)
     * Por enquanto, qualquer usuário autenticado pode acessar
     */
    public function viewAny(User $user): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function view(User $user, Database $database): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function create(User $user): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function update(User $user, Database $database): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function delete(User $user, Database $database): bool
    {
        return true; // TODO: Implementar RBAC
    }
}
