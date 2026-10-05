import { useEffect, useRef } from 'react';

export default <TEvent extends Event = Event>(
    eventName: string,
    handler: (event: TEvent) => void,
    options?: boolean | AddEventListenerOptions
) => {
    const savedHandler = useRef(handler);
    const normalizedOptions = options === true || options === false ? undefined : options;
    const capture = options === true || normalizedOptions?.capture === true;
    const hasOptions = options !== undefined;
    const once = normalizedOptions?.once;
    const passive = normalizedOptions?.passive;
    const signal = normalizedOptions?.signal;

    useEffect(() => {
        savedHandler.current = handler;
    }, [handler]);

    useEffect(() => {
        const target = globalThis.window;
        if (!target?.addEventListener) return;

        const eventListener = (event: Event) => savedHandler.current(event as TEvent);
        const listenerOptions = hasOptions ? { capture, once, passive, signal } : undefined;

        target.addEventListener(eventName, eventListener, listenerOptions);
        return () => {
            target.removeEventListener(eventName, eventListener, capture);
        };
    }, [capture, eventName, hasOptions, once, passive, signal]);
};
