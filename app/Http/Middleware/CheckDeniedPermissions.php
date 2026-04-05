<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class CheckDeniedPermissions
{
    /**
     * Handle an incoming request.
     *
     * Checks if the authenticated user has any denied permissions
     * that should prevent access to the current route.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Get denied permission IDs from user (stored as array of IDs)
        $deniedPermissionIds = $user->denied_permissions ?? [];

        if (empty($deniedPermissionIds)) {
            return $next($request);
        }

        // Get denied permission names
        $deniedPermissionNames = Permission::whereIn('id', $deniedPermissionIds)
            ->pluck('name')
            ->toArray();

        if (empty($deniedPermissionNames)) {
            return $next($request);
        }

        // Get required permission for current route (if defined)
        $requiredPermission = $this->getRequiredPermission($request);

        if ($requiredPermission && in_array($requiredPermission, $deniedPermissionNames, true)) {
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }

    /**
     * Get the required permission for the current route.
     *
     * Uses the route's 'permission' parameter or derives it from the route name.
     */
    private function getRequiredPermission(Request $request): ?string
    {
        $route = $request->route();

        if (!$route) {
            return null;
        }

        // Check if route has explicit permission parameter
        $permission = $route->parameter('permission');
        if ($permission) {
            return (string) $permission;
        }

        // Derive permission from route name for app routes
        // e.g., app.databases.index -> databases.view
        $routeName = $route->getName();
        if ($routeName && str_starts_with($routeName, 'app.')) {
            return $this->derivePermissionFromRoute($routeName);
        }

        return null;
    }

    /**
     * Derive permission name from route name.
     *
     * app.databases.index -> databases.view
     * app.databases.create -> databases.create
     * app.databases.store -> databases.create
     * app.databases.edit -> databases.edit
     * app.databases.update -> databases.edit
     * app.databases.destroy -> databases.delete
     */
    private function derivePermissionFromRoute(string $routeName): ?string
    {
        // Remove 'app.' prefix
        $name = str_replace('app.', '', $routeName);

        // Split into parts
        $parts = explode('.', $name);

        if (count($parts) < 2) {
            return null;
        }

        $resource = $parts[0]; // databases, schemas, credentials, etc.
        $action = $parts[1];   // index, create, store, edit, update, destroy, show

        // Map route actions to permission actions
        $permissionMap = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
        ];

        $mappedAction = $permissionMap[$action] ?? $action;

        return "{$resource}.{$mappedAction}";
    }
}
