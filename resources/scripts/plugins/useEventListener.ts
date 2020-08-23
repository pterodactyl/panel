import { useEffect, useRef } from 'react';

export default (eventName: string, handler: (e: Event | CustomEvent | UIEvent | any) => void, options?: boolean | EventListenerOptions) => {
    const savedHandler = useRef<any>(null);

    useEffect(() => {
        savedHandler.current = handler;
    }, [ handler ]);

    useEffect(() => {
        const isSupported = document && document.addEventListener;
        if (!isSupported) return;

        const eventListener = (event: any) => savedHandler.current(event);
        document.addEventListener(eventName, eventListener, options);
        return () => {
            document.removeEventListener(eventName, eventListener);
        };
    }, [ eventName, document ]);
};
