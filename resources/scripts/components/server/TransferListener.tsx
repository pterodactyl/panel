import { useEffect, useRef } from 'react';
import { useServerRouteId } from '@/router/params';
import { useQueryClient } from '@tanstack/react-query';
import { serverQueryFilters, useCurrentServer, useUpdateCurrentServer } from '@/api/server/queries';
import type { Server } from '@/api/server/queries';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { useSocketConnected } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import { isTransferSuccessful, normalizeTransferStatus } from '@/components/server/transfer';

const selectIsTransferring = (server: Server) => server.attributes.is_transferring;

const TransferListener = () => {
    const id = useServerRouteId();
    const queryClient = useQueryClient();
    const updateCurrentServer = useUpdateCurrentServer();
    const connected = useSocketConnected();
    const isTransferring = useCurrentServer(selectIsTransferring) ?? false;
    const wasConnected = useRef(connected);

    useEffect(() => {
        const disconnected = wasConnected.current && !connected;
        wasConnected.current = connected;

        if (disconnected && isTransferring) {
            queryClient.invalidateQueries(serverQueryFilters(id ?? '')).catch((error) => console.error(error));
        }
    }, [connected, id, isTransferring, queryClient]);

    useWebsocketEvent(SocketEvent.TRANSFER_STATUS, (value: string) => {
        const status = normalizeTransferStatus(value);
        if (!status) {
            return;
        }

        if (isTransferSuccessful(status)) {
            queryClient.refetchQueries(serverQueryFilters(id ?? '')).catch((error) => console.error(error));
            return;
        }

        const transferring = status !== 'failed';
        updateCurrentServer((server) =>
            server.attributes.is_transferring === transferring
                ? server
                : { ...server, attributes: { ...server.attributes, is_transferring: transferring } }
        );
    });

    return null;
};

export default TransferListener;
