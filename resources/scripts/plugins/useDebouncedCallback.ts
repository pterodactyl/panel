import { useEffect, useMemo, useRef } from 'react';

export type DebouncedCallback<TArgs extends unknown[]> = ((...args: TArgs) => void) & {
    cancel: () => void;
    flush: () => void;
};

/** A pending call still runs when the component unmounts. */
export const useDebouncedCallback = <TArgs extends unknown[]>(
    callback: (...args: TArgs) => void,
    delay: number
): DebouncedCallback<TArgs> => {
    const callbackRef = useRef(callback);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const pendingRef = useRef<TArgs | null>(null);

    useEffect(() => {
        callbackRef.current = callback;
    }, [callback]);

    const debounced = useMemo(() => {
        const cancel = () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
                timeoutRef.current = null;
            }
            pendingRef.current = null;
        };

        const flush = () => {
            const args = pendingRef.current;
            cancel();
            if (args) {
                callbackRef.current(...args);
            }
        };

        const call = (...args: TArgs) => {
            cancel();
            pendingRef.current = args;
            timeoutRef.current = setTimeout(flush, delay);
        };

        return Object.assign(call, { cancel, flush });
    }, [delay]);

    useEffect(() => debounced.flush, [debounced]);

    return debounced;
};
