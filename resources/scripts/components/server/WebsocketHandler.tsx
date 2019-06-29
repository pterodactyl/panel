import React, { useEffect } from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import Sockette from 'sockette';

export default () => {
    const server = useStoreState((state: State<ApplicationState>) => state.server.data);
    const instance = useStoreState((state: State<ApplicationState>) => state.server.socket.instance);
    const setInstance = useStoreActions((actions: Actions<ApplicationState>) => actions.server.socket.setInstance);
    const setConnectionState = useStoreActions((actions: Actions<ApplicationState>) => actions.server.socket.setConnectionState);

    useEffect(() => {
        // If there is already an instance or there is no server, just exit out of this process
        // since we don't need to make a new connection.
        if (instance || !server) {
            return;
        }

        console.log('need to connect to instance');
        const socket = new Sockette(`wss://wings.pterodactyl.test:8080/api/servers/${server.uuid}/ws`, {
            protocols: 'CC8kHCuMkXPosgzGO6d37wvhNcksWxG6kTrA',
            // onmessage: (ev) => console.log(ev),
            onopen: () => setConnectionState(true),
            onclose: () => setConnectionState(false),
            onerror: () => setConnectionState(false),
        });

        console.log('Setting instance!');

        setInstance(socket);
    }, [server]);

    return null;
};
