import { useMemo } from 'react';
import { serverConflict, type ServerConflict } from './state';
import { implementViews } from '../registry';
import { useLoadedServer } from './loadedServer';

export interface ServerConflictModel {
    /** Why the server's pages are unavailable, or null while they are available. */
    readonly kind: ServerConflict | null;
}

declare module '../registry' {
    interface ViewModels {
        'server.conflict': ServerConflictModel;
    }
}

function useConflictModel(): ServerConflictModel {
    const kind = serverConflict(useLoadedServer());

    return useMemo(() => ({ kind }), [kind]);
}

implementViews({
    'server.conflict': {
        use: useConflictModel,
        fixtures: {
            installing: () => ({ kind: 'installing' }),
            suspended: () => ({ kind: 'suspended' }),
            maintenance: () => ({ kind: 'maintenance' }),
            transferring: () => ({ kind: 'transferring' }),
            restoring: () => ({ kind: 'restoring-backup' }),
        },
    },
});
