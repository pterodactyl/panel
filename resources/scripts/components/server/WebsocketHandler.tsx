import { useCallback, useEffect, useRef } from 'react';
import { getServerWebsocketCredentials, useCurrentServer, useCurrentServerUuid } from '@/api/server/queries';
import type { Server } from '@/api/server/queries';
import { Websocket } from '@/plugins/Websocket';
import { useServerStore } from '@/state/server';
import type { ServerStatus } from '@/state/server';
import ContentContainer from '@/components/elements/ContentContainer';
import Spinner from '@/components/elements/Spinner';
import { dismissNotification, notifyHttpError, notifyServerError } from '@/plugins/notifications';

const reconnectErrors = ['jwt: exp claim is invalid', 'jwt: created too far in past (denylist)'];

const serverErrorToasts = {
    daemon: 'server:daemon-error',
    jwt: 'server:websocket:jwt-error',
    connection: 'server:websocket:connection-error',
    token: 'server:websocket:token-error',
};

const daemonErrorMessage = (message: string) =>
    message.trim() ? message : 'Wings reported an error without additional details.';

const serverStatuses = new Set<ServerStatus>(['offline', 'starting', 'stopping', 'running', null]);
const isServerStatus = (value: string): value is NonNullable<ServerStatus> => serverStatuses.has(value as ServerStatus);

const selectSocketNode = (server: Server) =>
    `${server.attributes.node}@${server.attributes.sftp_details.ip}:${server.attributes.sftp_details.port}`;

export default function WebsocketHandler() {
    const updatingToken = useRef(false);
    const activeSocket = useRef<Websocket | null>(null);
    const activeUuid = useRef<string | null>(null);
    const reconnecting = useServerStore((state) => state.socket.reconnecting);
    const uuid = useCurrentServerUuid();
    const node = useCurrentServer(selectSocketNode);
    const setServerStatus = useServerStore((state) => state.status.setServerStatus);
    const { setInstance, setConnectionState, setReconnecting } = useServerStore((state) => state.socket);

    const updateToken = useCallback((uuid: string, socket: Websocket) => {
        if (updatingToken.current || activeSocket.current !== socket || activeUuid.current !== uuid) {
            return;
        }

        updatingToken.current = true;
        getServerWebsocketCredentials(uuid)
            .then((data) => {
                if (activeSocket.current !== socket || activeUuid.current !== uuid) {
                    return;
                }

                socket.setToken(data.token, true);
            })
            .catch((error) => {
                if (activeSocket.current === socket && activeUuid.current === uuid) {
                    notifyHttpError(error, 'Failed to refresh server connection', { id: serverErrorToasts.token });
                }
            })
            .finally(() => {
                updatingToken.current = false;
            });
    }, []);

    const createSocket = useCallback(
        (uuid: string) => {
            const socket = new Websocket();

            // react-doctor-disable-next-line react-doctor/effect-needs-cleanup
            socket.on('auth success', () => {
                setReconnecting(false);
                setConnectionState(true);
                dismissNotification(serverErrorToasts.connection);
                dismissNotification(serverErrorToasts.jwt);
                dismissNotification(serverErrorToasts.token);
            });
            socket.on('SOCKET_CLOSE', () => setConnectionState(false));
            socket.on('SOCKET_CONNECT_ERROR', () => {
                setReconnecting(false);
                notifyServerError('Server connection failed', {
                    id: serverErrorToasts.connection,
                    description:
                        'Failed to connect to the websocket after multiple attempts. Reconnecting resumes once your network or this tab is active again.',
                });
            });
            socket.on('SOCKET_RECONNECT', () => {
                setReconnecting(true);
                setConnectionState(false);
            });
            socket.on('SOCKET_ERROR', () => {
                setReconnecting(true);
                setConnectionState(false);
            });
            socket.on('status', (status) => {
                if (isServerStatus(status)) {
                    setServerStatus(status);
                }
            });

            socket.on('daemon error', (message) => {
                console.warn('Got error message from daemon socket:', message);
                notifyServerError('Server reported an error', {
                    id: serverErrorToasts.daemon,
                    description: daemonErrorMessage(message),
                });
            });

            socket.on('token expiring', () => updateToken(uuid, socket));
            socket.on('token expired', () => updateToken(uuid, socket));
            socket.on('jwt error', (error: string) => {
                setConnectionState(false);
                console.warn('JWT validation error from wings:', error);

                if (reconnectErrors.some((v) => error.toLowerCase().includes(v))) {
                    updateToken(uuid, socket);
                } else {
                    setReconnecting(false);
                    notifyServerError('Server connection rejected', {
                        id: serverErrorToasts.jwt,
                        description:
                            'There was an error validating the credentials provided for the websocket. Please refresh the page.',
                    });
                }
            });

            return socket;
        },
        [setConnectionState, setReconnecting, setServerStatus, updateToken]
    );

    // Reconnects with fresh credentials whenever the server moves to another node.
    useEffect(() => {
        if (!uuid || !node) {
            return;
        }

        let cancelled = false;
        const socket = createSocket(uuid);

        activeSocket.current = socket;
        activeUuid.current = uuid;
        setConnectionState(false);
        setInstance(null);

        getServerWebsocketCredentials(uuid)
            .then((data) => {
                if (cancelled || activeSocket.current !== socket || activeUuid.current !== uuid) {
                    socket.close();

                    return;
                }

                socket.setToken(data.token).connect(data.socket);
                setInstance(socket);
            })
            .catch((error) => {
                if (cancelled || activeSocket.current !== socket || activeUuid.current !== uuid) {
                    return;
                }

                setReconnecting(false);
                notifyHttpError(error, 'Failed to start server connection', { id: serverErrorToasts.token });
            });

        return () => {
            cancelled = true;

            if (activeSocket.current === socket) {
                activeSocket.current = null;
                activeUuid.current = null;
                updatingToken.current = false;
                setInstance(null);
                setConnectionState(false);
            }

            socket.removeAllListeners();
            socket.close();
        };
    }, [createSocket, node, setConnectionState, setInstance, setReconnecting, uuid]);

    return reconnecting ? (
        <div className='bg-destructive py-2'>
            <ContentContainer className='flex items-center justify-center'>
                <Spinner size='small' />
                <p className='ml-2 text-sm text-destructive-foreground'>
                    We&apos;re having some trouble connecting to your server, please wait...
                </p>
            </ContentContainer>
        </div>
    ) : null;
}
