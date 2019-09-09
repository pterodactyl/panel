import React, { useEffect } from 'react';
import { Websocket } from '@/plugins/Websocket';
import { ServerContext } from '@/state/server';

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data);
    const instance = ServerContext.useStoreState(state => state.socket.instance);
    const setServerStatus = ServerContext.useStoreActions(actions => actions.status.setServerStatus);
    const { setInstance, setConnectionState } = ServerContext.useStoreActions(actions => actions.socket);

    useEffect(() => {
        // If there is already an instance or there is no server, just exit out of this process
        // since we don't need to make a new connection.
        if (instance || !server) {
            return;
        }

        const socket = new Websocket(server.uuid);

        socket.on('SOCKET_OPEN', () => setConnectionState(true));
        socket.on('SOCKET_CLOSE', () => setConnectionState(false));
        socket.on('SOCKET_ERROR', () => setConnectionState(false));
        socket.on('status', (status) => setServerStatus(status));

        socket.connect()
            .then(() => setInstance(socket))
            .catch(error => console.error(error));

        return () => {
            socket && socket.close();
            instance && instance!.removeAllListeners();
        };
    }, [ server ]);

    return null;
};
