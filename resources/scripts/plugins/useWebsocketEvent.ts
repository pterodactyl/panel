import { ServerContext } from '@/state/server';
import { useEffect, useRef } from 'react';
import { SocketEvent } from '@/components/server/events';

const useWebsocketEvent = (event: SocketEvent, callback: (data: string) => void) => {
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);
    const savedCallback = useRef<any>(null);

    useEffect(() => {
        savedCallback.current = callback;
    }, [callback]);

    return useEffect(() => {
        const eventListener = (event: SocketEvent) => savedCallback.current(event);
        if (connected && instance) {
            instance.addListener(event, eventListener);
        }

        return () => {
            instance && instance.removeListener(event, eventListener);
        };
    }, [event, connected, instance]);
};

export default useWebsocketEvent;
