import { useCurrentServerPermissions } from '@/api/server/queries';

export const hasPermission = (userPermissions: readonly string[], permission: string): boolean => {
    if (userPermissions[0] === '*') {
        return true;
    }

    if (permission.endsWith('.*')) {
        const prefix = permission.slice(0, -1);

        return userPermissions.some((granted) => granted.startsWith(prefix));
    }

    return userPermissions.includes(permission);
};

export const usePermissions = (action: string | string[]): boolean[] => {
    const userPermissions = useCurrentServerPermissions();

    return (Array.isArray(action) ? action : [action]).map((permission) => hasPermission(userPermissions, permission));
};
