import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';

const InstallListener = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    // Listen for the installation completion event and then fire off a request to fetch the updated
    // server information. This allows the server to automatically become available to the user if they
    // just sit on the page.
    useWebsocketEvent('install completed', () => {
        getServer(uuid).catch(error => console.error(error));
    });

    // When we see the install started event immediately update the state to indicate such so that the
    // screens automatically update.
    useWebsocketEvent('install started', () => {
        setServerFromState(s => ({ ...s, isInstalling: true }));
    });

    return null;
};

export default InstallListener;
