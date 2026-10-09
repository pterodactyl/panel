import { useEffect, useRef } from 'react';
import { useSocketConnected, useSocketInstance } from '@/state/server';
import type { SocketEvent } from '@/components/server/events';

const useWebsocketEvent = (event: SocketEvent, callback: (data: string) => void) => {
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const savedCallback = useRef(callback);

    useEffect(() => {
        savedCallback.current = callback;
    }, [callback]);

    return useEffect(() => {
        const eventListener = (data: string) => savedCallback.current(data);

        if (connected && instance) {
            instance.addListener(event, eventListener);
        }

        return () => {
            instance?.removeListener(event, eventListener);
        };
    }, [event, connected, instance]);
};

export default useWebsocketEvent;
