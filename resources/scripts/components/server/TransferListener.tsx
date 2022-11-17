import { ServerContext } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';

const TransferListener = () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions((actions) => actions.server.getServer);
    const setServerFromState = ServerContext.useStoreActions((actions) => actions.server.setServerFromState);

    // Listen for the transfer status event, so we can update the state of the server.
    useWebsocketEvent(SocketEvent.TRANSFER_STATUS, (status: string) => {
        if (status === 'pending' || status === 'processing') {
            setServerFromState((s) => ({ ...s, isTransferring: true }));
            return;
        }

        if (status === 'failed') {
            setServerFromState((s) => ({ ...s, isTransferring: false }));
            return;
        }

        if (status !== 'completed') {
            return;
        }

        // Refresh the server's information as it's node and allocations were just updated.
        getServer(uuid).catch((error) => console.error(error));
    });

    return null;
};

export default TransferListener;
