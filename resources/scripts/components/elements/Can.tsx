import React, { useMemo } from 'react';
import { ServerContext } from '@/state/server';

interface Props {
    action: string | string[];
    renderOnError?: React.ReactNode | null;
    children: React.ReactNode;
}

const Can = ({ action, renderOnError, children }: Props) => {
    const userPermissions = ServerContext.useStoreState(state => state.server.permissions);
    const actions = Array.isArray(action) ? action : [ action ];

    const missingPermissionCount = useMemo(() => {
        if (userPermissions[0] === '*') {
            return 0;
        }

        return actions.filter(permission => {
            return !(
                // Allows checking for any permission matching a name, for example files.*
                // will return if the user has any permission under the file.XYZ namespace.
                (
                    permission.endsWith('.*') &&
                    permission !== 'websocket.*' &&
                    userPermissions.filter(p => p.startsWith(permission.split('.')[0])).length > 0
                )
                // Otherwise just check if the entire permission exists in the array or not.
                || userPermissions.indexOf(permission) >= 0);
        }).length;
    }, [ action, userPermissions ]);

    return (
        <>
            {missingPermissionCount > 0 ?
                renderOnError
                :
                children
            }
        </>
    );
};

export default Can;
