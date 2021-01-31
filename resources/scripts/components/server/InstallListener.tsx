import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';

const InstallListener = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const { mutate } = useFileManagerSwr();
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    useWebsocketEvent(SocketEvent.BACKUP_RESTORE_COMPLETED, () => {
        mutate(undefined);
        setServerFromState(s => ({ ...s, status: null }));
    });

    // Listen for the installation completion event and then fire off a request to fetch the updated
    // server information. This allows the server to automatically become available to the user if they
    // just sit on the page.
    useWebsocketEvent(SocketEvent.INSTALL_COMPLETED, () => {
        getServer(uuid).catch(error => console.error(error));
    });

    // When we see the install started event immediately update the state to indicate such so that the
    // screens automatically update.
    useWebsocketEvent(SocketEvent.INSTALL_STARTED, () => {
        setServerFromState(s => ({ ...s, status: 'installing' }));
    });

    return null;
};

export default InstallListener;
