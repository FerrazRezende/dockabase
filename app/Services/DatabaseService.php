<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Credential;
use App\Models\Database;
use App\Models\User;
use Illuminate\Support\Collection;

class DatabaseService
{
    public function create(array $data): Database
    {
        return Database::create([
            'name' => $data['name'],
            'display_name' => $data['display_name'] ?? null,
            'description' => $data['description'] ?? null,
            'host' => $data['host'] ?? 'localhost',
            'port' => $data['port'] ?? 5432,
            'database_name' => $data['database_name'],
            'is_active' => $data['is_active'] ?? false,
            'settings' => $data['settings'] ?? null,
            'created_by' => $data['created_by'] ?? null,
        ]);
    }

    public function update(Database $database, array $data): Database
    {
        $database->update($data);

        return $database->fresh();
    }

    public function delete(string $id): void
    {
        Database::destroy($id);
    }

    public function attachCredential(Database $database, Credential $credential): void
    {
        $database->credentials()->syncWithoutDetaching($credential);
    }

    public function detachCredential(Database $database, Credential $credential): void
    {
        $database->credentials()->detach($credential);
    }

    public function getDatabasesForUser(User $user): Collection
    {
        return Database::whereHas('credentials.users', fn ($q) => $q->where('users.id', $user->id))
            ->active()
            ->get();
    }
}
