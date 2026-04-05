import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const permissions = computed(() => {
        return (page.props.userPermissions as string[]) || [];
    });

    /**
     * Check if user has a specific permission
     */
    const hasPermission = (permission: string): boolean => {
        return permissions.value.includes(permission);
    };

    /**
     * Check if user has ANY of the given permissions
     */
    const hasAnyPermission = (...perms: string[]): boolean => {
        return perms.some(p => permissions.value.includes(p));
    };

    /**
     * Check if user has ALL of the given permissions
     */
    const hasAllPermissions = (...perms: string[]): boolean => {
        return perms.every(p => permissions.value.includes(p));
    };

    /**
     * Permission groups for common operations
     */
    const canView = (resource: string): boolean => {
        return hasPermission(`${resource}.view`);
    };

    const canCreate = (resource: string): boolean => {
        return hasPermission(`${resource}.create`);
    };

    const canEdit = (resource: string): boolean => {
        return hasPermission(`${resource}.edit`);
    };

    const canDelete = (resource: string): boolean => {
        return hasPermission(`${resource}.delete`);
    };

    return {
        permissions,
        hasPermission,
        hasAnyPermission,
        hasAllPermissions,
        canView,
        canCreate,
        canEdit,
        canDelete,
    };
}
