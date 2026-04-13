<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\System\PermissionResource;
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
        $roles = \Spatie\Permission\Models\Role::with('permissions')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(50);

        if ($request->wantsJson()) {
            return PermissionResource::collection($permissions);
        }

        return Inertia::render('System/Permissions/Index', [
            'permissions' => $permissions->items(),
            'roles' => $roles->items(),
        ]);
    }
}
