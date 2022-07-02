import { useEffect, useRef } from 'react';

export default (
    eventName: string,
    handler: (e: Event | CustomEvent | UIEvent | any) => void,
    options?: boolean | EventListenerOptions
) => {
    const savedHandler = useRef<any>(null);

    useEffect(() => {
        savedHandler.current = handler;
    }, [handler]);

    useEffect(() => {
        const isSupported = window && window.addEventListener;
        if (!isSupported) return;

        const eventListener = (event: any) => savedHandler.current(event);
        window.addEventListener(eventName, eventListener, options);
        return () => {
            window.removeEventListener(eventName, eventListener);
        };
    }, [eventName, window]);
};
