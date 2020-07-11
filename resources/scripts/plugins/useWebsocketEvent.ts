import { ServerContext } from '@/state/server';
import { useEffect, useRef } from 'react';

const useWebsocketEvent = (event: string, callback: (data: string) => void) => {
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);
    const savedCallback = useRef<any>(null);

    useEffect(() => {
        savedCallback.current = callback;
    }, [ callback ]);

    return useEffect(() => {
        const eventListener = (event: any) => savedCallback.current(event);
        if (connected && instance) {
            instance.addListener(event, eventListener);
        }

        return () => {
            instance && instance.removeListener(event, eventListener);
        };
    }, [ event, connected, instance ]);
};

export default useWebsocketEvent;
