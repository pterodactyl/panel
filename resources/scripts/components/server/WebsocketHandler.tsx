import React, { useEffect } from 'react';
import { Websocket } from '@/plugins/Websocket';
import { ServerContext } from '@/state/server';
import getWebsocketToken from '@/api/server/getWebsocketToken';

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data);
    const { instance } = ServerContext.useStoreState(state => state.socket);
    const setServerStatus = ServerContext.useStoreActions(actions => actions.status.setServerStatus);
    const { setInstance, setConnectionState } = ServerContext.useStoreActions(actions => actions.socket);

    const updateToken = (uuid: string, socket: Websocket) => {
        getWebsocketToken(uuid)
            .then(data => socket.setToken(data.token, true))
            .catch(error => console.error(error));
    };

    useEffect(() => {
        // If there is already an instance or there is no server, just exit out of this process
        // since we don't need to make a new connection.
        if (instance || !server) {
            return;
        }

        const socket = new Websocket();

        socket.on('auth success', () => setConnectionState(true));
        socket.on('SOCKET_CLOSE', () => setConnectionState(false));
        socket.on('SOCKET_ERROR', () => setConnectionState(false));
        socket.on('status', (status) => setServerStatus(status));

        socket.on('daemon error', message => {
            console.warn('Got error message from daemon socket:', message);
        });

        socket.on('token expiring', () => updateToken(server.uuid, socket));
        socket.on('token expired', () => updateToken(server.uuid, socket));

        getWebsocketToken(server.uuid)
            .then(data => {
                // Connect and then set the authentication token.
                socket.setToken(data.token).connect(data.socket);

                // Once that is done, set the instance.
                setInstance(socket);
            })
            .catch(error => console.error(error));
    }, [ server ]);

    return null;
};
