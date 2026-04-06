<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StoreRoleRequest;
use App\Http\Requests\System\SyncRolePermissionsRequest;
use App\Http\Requests\System\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Show the form for creating a new role.
     */
    public function create(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        return Inertia::render('System/Roles/Form', [
            'allPermissions' => Permission::orderBy('name')->get(['id', 'name', 'guard_name']),
        ]);
    }

    /**
     * Show the form for editing the specified role.
     */
    public function edit(Request $request, Role $role)
    {
        abort_unless($request->user()->is_admin, 403);

        $role->load('permissions');

        return Inertia::render('System/Roles/Form', [
            'role' => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ])->toArray(),
            ],
            'allPermissions' => Permission::orderBy('name')->get(['id', 'name', 'guard_name']),
        ]);
    }

    /**
     * Display a listing of roles.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(50);

        if ($request->wantsJson()) {
            return RoleResource::collection($roles);
        }

        return Inertia::render('System/Roles/Index', [
            'roles' => RoleResource::collection($roles)->toArray($request),
            'permissions' => Permission::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        $permissions = $request->input('permissions', []);
        if (!empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        if ($request->wantsJson()) {
            return new RoleResource($role->load('permissions'));
        }

        return redirect()->route('system.permissions.index')->with('success', __('Role created successfully'));
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, Role $role)
    {
        abort_unless($request->user()->is_admin, 403);

        $role->update([
            'name' => $request->validated('name'),
        ]);

        $permissions = $request->input('permissions', []);
        $role->syncPermissions($permissions);

        if ($request->wantsJson()) {
            return new RoleResource($role->load('permissions'));
        }

        return redirect()->route('system.permissions.index')->with('success', __('Role updated successfully'));
    }

    /**
     * Sync permissions for the specified role.
     */
    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role)
    {
        abort_unless($request->user()->is_admin, 403);

        $role->syncPermissions($request->validated('permissions', []));

        if ($request->wantsJson()) {
            return new RoleResource($role->load('permissions'));
        }

        return redirect()->back()->with('success', __('Permissions synced successfully'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, Role $role)
    {
        abort_unless($request->user()->is_admin, 403);

        // Check if role has users
        if ($role->users()->exists()) {
            return redirect()->back()->withErrors([
                'error' => __('Cannot delete role that is assigned to users'),
            ]);
        }

        $role->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => __('Role deleted successfully')]);
        }

        return redirect()->back()->with('success', __('Role deleted successfully'));
    }
}
