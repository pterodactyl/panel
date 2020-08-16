import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';
import useServer from '@/plugins/useServer';

const InstallListener = () => {
    const server = useServer();
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const setServer = ServerContext.useStoreActions(actions => actions.server.setServer);

    // Listen for the installation completion event and then fire off a request to fetch the updated
    // server information. This allows the server to automatically become available to the user if they
    // just sit on the page.
    useWebsocketEvent('install completed', () => {
        getServer(server.uuid).catch(error => console.error(error));
    });

    // When we see the install started event immediately update the state to indicate such so that the
    // screens automatically update.
    useWebsocketEvent('install started', () => {
        setServer({ ...server, isInstalling: true });
    });

    return null;
};

export default InstallListener;
