import React, { useEffect, useState } from 'react';
import { Websocket } from '@/plugins/Websocket';
import { ServerContext } from '@/state/server';
import getWebsocketToken from '@/api/server/getWebsocketToken';
import ContentContainer from '@/components/elements/ContentContainer';
import { CSSTransition } from 'react-transition-group';
import Spinner from '@/components/elements/Spinner';
import tw from 'twin.macro';

export default () => {
    const server = ServerContext.useStoreState(state => state.server.data);
    const [ error, setError ] = useState(false);
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);
    const setServerStatus = ServerContext.useStoreActions(actions => actions.status.setServerStatus);
    const { setInstance, setConnectionState } = ServerContext.useStoreActions(actions => actions.socket);

    const updateToken = (uuid: string, socket: Websocket) => {
        getWebsocketToken(uuid)
            .then(data => socket.setToken(data.token, true))
            .catch(error => console.error(error));
    };

    useEffect(() => {
        connected && setError(false);
    }, [ connected ]);

    useEffect(() => {
        return () => {
            instance && instance.close();
        };
    }, [ instance ]);

    useEffect(() => {
        // If there is already an instance or there is no server, just exit out of this process
        // since we don't need to make a new connection.
        if (instance || !server) {
            return;
        }

        const socket = new Websocket();

        socket.on('auth success', () => setConnectionState(true));
        socket.on('SOCKET_CLOSE', () => setConnectionState(false));
        socket.on('SOCKET_ERROR', () => {
            setError(true);
            setConnectionState(false);
        });
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

    return (
        error ?
            <CSSTransition timeout={150} in appear classNames={'fade'}>
                <div css={tw`bg-red-500 py-2`}>
                    <ContentContainer css={tw`flex items-center justify-center`}>
                        <Spinner size={'small'}/>
                        <p css={tw`ml-2 text-sm text-red-100`}>
                            We&apos;re having some trouble connecting to your server, please wait...
                        </p>
                    </ContentContainer>
                </div>
            </CSSTransition>
            :
            null
    );
};
