import { useEffect } from 'react';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import { parseServerStatsPayload } from '@/components/server/console/stats';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { useServerStatus, useServerStore, useSocketConnected, useSocketInstance } from '@/state/server';

/** Records the server's resource usage once for the whole server area, however many views show it. */
export default function StatsListener() {
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const status = useServerStatus();
    const push = useServerStore((state) => state.stats.push);
    const clearGraphs = useServerStore((state) => state.stats.clearGraphs);

    useEffect(() => {
        if (connected && instance) {
            instance.send(SocketRequest.SEND_STATS);
        }
    }, [instance, connected]);

    useEffect(() => {
        if (status === 'offline') {
            clearGraphs();
        }
    }, [clearGraphs, status]);

    useWebsocketEvent(SocketEvent.STATS, (data) => {
        const stats = parseServerStatsPayload(data);

        if (stats) {
            push(stats, performance.now());
        }
    });

    return null;
}
