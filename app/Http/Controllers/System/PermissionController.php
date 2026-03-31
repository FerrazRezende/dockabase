<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\System\StorePermissionRequest;
use App\Http\Requests\System\UpdatePermissionRequest;
use App\Http\Resources\PermissionResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(Request $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $permissions = Permission::orderBy('name')->paginate(50);

        if ($request->wantsJson()) {
            return PermissionResource::collection($permissions);
        }

        return Inertia::render('System/Permissions/Index', [
            'permissions' => PermissionResource::collection($permissions)->toArray($request),
        ]);
    }

    /**
     * Store a newly created permission.
     */
    public function store(StorePermissionRequest $request)
    {
        abort_unless($request->user()->is_admin, 403);

        $permission = Permission::create($request->validated());

        if ($request->wantsJson()) {
            return new PermissionResource($permission);
        }

        return redirect()->back()->with('success', 'Permission created successfully.');
    }

    /**
     * Update the specified permission.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        abort_unless($request->user()->is_admin, 403);

        $permission->update($request->validated());

        if ($request->wantsJson()) {
            return new PermissionResource($permission);
        }

        return redirect()->back()->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(Request $request, Permission $permission)
    {
        abort_unless($request->user()->is_admin, 403);

        // Check if permission is in use
        if ($permission->roles()->exists()) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot delete permission that is assigned to roles.',
            ]);
        }

        $permission->delete();

        if ($request->wantsJson()) {
            return response()->json(['message' => 'Permission deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Permission deleted successfully.');
    }
}
