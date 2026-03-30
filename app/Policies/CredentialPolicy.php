<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    /**
     * Credentials - Admin only
     * Apenas administradores podem gerenciar credenciais
     */
    public function viewAny(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function view(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }

    public function create(User $user): bool
    {
        return $user->is_admin === true;
    }

    public function update(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }

    public function delete(User $user, Credential $credential): bool
    {
        return $user->is_admin === true;
    }
}
