import React, { useEffect } from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import { Websocket } from '@/plugins/Websocket';

export default () => {
    const server = useStoreState((state: State<ApplicationState>) => state.server.data);
    const instance = useStoreState((state: State<ApplicationState>) => state.server.socket.instance);
    const setServerStatus = useStoreActions((actions: Actions<ApplicationState>) => actions.server.setServerStatus);
    const { setInstance, setConnectionState } = useStoreActions((actions: Actions<ApplicationState>) => actions.server.socket);

    useEffect(() => {
        // If there is already an instance or there is no server, just exit out of this process
        // since we don't need to make a new connection.
        if (instance || !server) {
            return;
        }

        console.log('Connecting!');

        const socket = new Websocket(
            `wss://wings.pterodactyl.test:8080/api/servers/${server.uuid}/ws`,
            'CC8kHCuMkXPosgzGO6d37wvhNcksWxG6kTrA'
        );

        socket.on('SOCKET_OPEN', () => setConnectionState(true));
        socket.on('SOCKET_CLOSE', () => setConnectionState(false));
        socket.on('SOCKET_ERROR', () => setConnectionState(false));
        socket.on('status', (status) => setServerStatus(status));

        setInstance(socket);
    }, [ server ]);

    return null;
};
