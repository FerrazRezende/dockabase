<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CredentialPermissionEnum;
use App\Models\Credential;
use App\Models\Database;
use App\Models\User;

class CredentialService
{
    public function create(array $data): Credential
    {
        $credential = Credential::create([
            'name' => $data['name'],
            'permission' => $data['permission'] ?? CredentialPermissionEnum::Read,
            'description' => $data['description'] ?? null,
        ]);

        if (! empty($data['user_ids'])) {
            $credential->users()->attach($data['user_ids']);
        }

        return $credential->fresh();
    }

    public function update(Credential $credential, array $data): Credential
    {
        $credential->update([
            'name' => $data['name'] ?? $credential->name,
            'permission' => $data['permission'] ?? $credential->permission,
            'description' => $data['description'] ?? $credential->description,
        ]);

        if (isset($data['user_ids'])) {
            $credential->users()->sync($data['user_ids']);
        }

        return $credential->fresh();
    }

    public function delete(string $id): void
    {
        Credential::destroy($id);
    }

    public function attachUser(Credential $credential, string $userId): void
    {
        $credential->users()->syncWithoutDetaching($userId);
    }

    public function detachUser(Credential $credential, string $userId): void
    {
        $credential->users()->detach($userId);
    }

    public function getUserPermissionForDatabase(User $user, string $databaseName): ?CredentialPermissionEnum
    {
        $database = Database::where('name', $databaseName)->first();

        if (! $database) {
            return null;
        }

        $credential = $database->credentials()
            ->whereHas('users', fn ($q) => $q->where('users.id', $user->id))
            ->first();

        return $credential?->permission;
    }
}
