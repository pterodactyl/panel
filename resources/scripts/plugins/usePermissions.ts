import { ServerContext } from '@/state/server';
import { useDeepCompareMemo } from '@/plugins/useDeepCompareMemo';

export const usePermissions = (action: string | string[]): boolean[] => {
    const userPermissions = ServerContext.useStoreState(state => state.server.permissions);

    return useDeepCompareMemo(() => {
        if (userPermissions[0] === '*') {
            return Array(Array.isArray(action) ? action.length : 1).fill(true);
        }

        return (Array.isArray(action) ? action : [action]).map(
            permission =>
                // Allows checking for any permission matching a name, for example files.*
                // will return if the user has any permission under the file.XYZ namespace.
                (permission.endsWith('.*') &&
                    userPermissions.filter(p => p.startsWith(permission.split('.')?.[0] ?? '')).length > 0) ||
                // Otherwise just check if the entire permission exists in the array or not.
                userPermissions.indexOf(permission) >= 0,
        );
    }, [action, userPermissions]);
};
