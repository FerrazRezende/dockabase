<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    /**
     * Credentials - RBAC (quando implementado)
     * Por enquanto, qualquer usuário autenticado pode acessar
     */
    public function viewAny(User $user): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function view(User $user, Credential $credential): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function create(User $user): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function update(User $user, Credential $credential): bool
    {
        return true; // TODO: Implementar RBAC
    }

    public function delete(User $user, Credential $credential): bool
    {
        return true; // TODO: Implementar RBAC
    }
}
