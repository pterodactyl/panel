import { useRef } from 'react';
import isEqual from 'react-fast-compare';

export const useDeepMemo = <T, K> (fn: () => T, key: K): T => {
    const ref = useRef<{ key: K, value: T }>();

    if (!ref.current || !isEqual(key, ref.current.key)) {
        ref.current = { key, value: fn() };
    }

    return ref.current.value;
};
