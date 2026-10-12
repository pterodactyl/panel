import { useCurrentServer } from '@/api/server/queries';
import type { Server } from '@/api/server/types';

/** The current server, which the server area loads before any of its views render. */
export function useLoadedServer(): Server {
    const server = useCurrentServer();

    if (!server) {
        throw new Error('Server views render once their server has loaded.');
    }

    return server;
}
