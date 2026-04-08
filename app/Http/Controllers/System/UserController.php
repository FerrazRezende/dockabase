<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreUserRequest;
use App\Http\Requests\System\SyncDirectPermissionsRequest;
use App\Http\Requests\System\UpdateUserRequest;
use App\Http\Requests\System\UpdateUserRoleRequest;
use App\Http\Resources\UserProfileResource;
use App\Http\Resources\SystemUserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $search = $request->input('search');

        $users = User::with('roles', 'permissions')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20);

        if ($request->wantsJson()) {
            return SystemUserResource::collection($users);
        }

        return Inertia::render('System/Users/Index', [
            'users' => SystemUserResource::collection($users)->toArray($request),
            'allRoles' => Role::orderBy('name')->get(['id', 'name']),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make('password123'),
            'password_changed_at' => null,
            'active' => true,
        ]);

        // Assign role if provided
        if ($request->has('role_id')) {
            $user->assignRole($request->validated('role_id'));
        }

        if ($request->wantsJson()) {
            return new SystemUserResource($user->load('roles', 'permissions'));
        }

        return redirect()->back()->with('success', __('User created successfully'));
    }

    /**
     * Display the specified user (full profile).
     */
    public function show(Request $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403);

        $user->load(['roles.permissions', 'permissions', 'credentials.databases']);

        if ($request->wantsJson()) {
            return new UserProfileResource($user);
        }

        return Inertia::render('System/Users/Show', [
            'user' => (new UserProfileResource($user))->toArray($request),
            'allRoles' => Role::orderBy('name')->get(['id', 'name']),
            'allPermissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403);

        $user->update($request->validated());

        if ($request->wantsJson()) {
            return new SystemUserResource($user->load('roles', 'permissions'));
        }

        return redirect()->back()->with('success', __('User updated successfully'));
    }

    /**
     * Update the role for the specified user.
     */
    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403);

        // Remove all existing roles and assign the new one (if provided)
        $user->syncRoles([]);

        if ($request->has('role_id')) {
            $user->assignRole($request->validated('role_id'));
        }

        // Reload relationships
        $user->load(['roles.permissions', 'permissions', 'credentials.databases']);

        if ($request->wantsJson()) {
            return new UserProfileResource($user);
        }

        return back()->with('success', __('Role updated successfully'));
    }

    /**
     * Sync direct permissions for the specified user (extra permissions beyond role).
     */
    public function syncPermissions(SyncDirectPermissionsRequest $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403);

        // Sync direct permissions only (role permissions are kept)
        $user->syncPermissions($request->validated('permissions', []));

        // Sync denied permissions (explicitly revoked from role)
        $user->denied_permissions = $request->validated('denied_permissions', []);
        $user->save();

        // Reload relationships
        $user->load(['roles.permissions', 'permissions', 'credentials.databases']);

        if ($request->wantsJson()) {
            return new UserProfileResource($user);
        }

        return back()->with('success', __('Permissions synced successfully'));
    }

    /**
     * Remove the specified user (deactivate).
     */
    public function destroy(Request $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403);

        // Don't delete the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return redirect()->back()->withErrors([
                'error' => __('Cannot delete the last admin user'),
            ]);
        }

        // Deactivate instead of deleting
        $user->update(['active' => false]);

        if ($request->wantsJson()) {
            return response()->json(['message' => __('User deactivated successfully')]);
        }

        return redirect()->back()->with('success', __('User deactivated successfully'));
    }
}
