import { useEffect, useRef } from 'react';

export default (eventName: string, handler: (e: Event | CustomEvent | UIEvent | any) => void, element: any = window) => {
    const savedHandler = useRef<any>(null);

    useEffect(() => {
        savedHandler.current = handler;
    }, [ handler ]);

    useEffect(
        () => {
            const isSupported = element && element.addEventListener;
            if (!isSupported) return;

            const eventListener = (event: any) => savedHandler.current(event);
            element.addEventListener(eventName, eventListener);
            return () => {
                element.removeEventListener(eventName, eventListener);
            };
        },
        [ eventName, element ],
    );
};
