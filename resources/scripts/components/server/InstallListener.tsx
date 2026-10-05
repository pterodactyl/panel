import { useServerRouteId } from '@/router/params';
import { useQueryClient } from '@tanstack/react-query';
import {
    serverQueryFilters,
    serverResourceQueryFilters,
    useUpdateCurrentServer,
    useCurrentServerUuid,
} from '@/api/server/queries';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { SocketEvent } from '@/components/server/events';

// Server resources answer 409 while an install or restore runs, so refetch them once it ends.
const InstallListener = () => {
    const id = useServerRouteId();
    const uuid = useCurrentServerUuid()!;
    const queryClient = useQueryClient();
    const updateCurrentServer = useUpdateCurrentServer();

    const refreshServer = () =>
        Promise.all([
            queryClient.refetchQueries(serverQueryFilters(id ?? '')),
            queryClient.invalidateQueries(serverResourceQueryFilters(uuid)),
        ]).catch((error) => console.error(error));

    useWebsocketEvent(SocketEvent.BACKUP_RESTORE_COMPLETED, () => {
        updateCurrentServer((server) => ({ ...server, attributes: { ...server.attributes, status: null } }));
        void refreshServer();
    });

    useWebsocketEvent(SocketEvent.INSTALL_COMPLETED, () => void refreshServer());

    useWebsocketEvent(SocketEvent.INSTALL_STARTED, () => {
        updateCurrentServer((server) => ({ ...server, attributes: { ...server.attributes, status: 'installing' } }));
    });

    return null;
};

export default InstallListener;
