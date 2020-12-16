import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';

const TransferListener = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    // Listen for the installation completion event and then fire off a request to fetch the updated
    // server information. This allows the server to automatically become available to the user if they
    // just sit on the page.
    useWebsocketEvent('transfer status', (status: string) => {
        if (status === 'starting') {
            setServerFromState(s => ({ ...s, isTransferring: true }));
            return;
        }

        if (status !== 'success') {
            return;
        }

        getServer(uuid).catch(error => console.error(error));
    });

    return null;
};

export default TransferListener;
