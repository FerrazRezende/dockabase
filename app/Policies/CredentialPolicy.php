<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->checkPermission('credentials.view');
    }

    public function view(User $user, Credential $credential): bool
    {
        if (! $user->checkPermission('credentials.view')) {
            return false;
        }

        return $this->canAccess($user, $credential);
    }

    public function create(User $user): bool
    {
        return $user->checkPermission('credentials.create');
    }

    public function update(User $user, Credential $credential): bool
    {
        if (! $user->checkPermission('credentials.update')) {
            return false;
        }

        return $this->canAccess($user, $credential);
    }

    public function delete(User $user, Credential $credential): bool
    {
        if (! $user->checkPermission('credentials.delete')) {
            return false;
        }

        return $this->canAccess($user, $credential);
    }

    private function canAccess(User $user, Credential $credential): bool
    {
        if ($user->is_admin) {
            return true;
        }

        if ($credential->created_by === $user->id) {
            return true;
        }

        return $credential->users()->where('users.id', $user->id)->exists();
    }
}
