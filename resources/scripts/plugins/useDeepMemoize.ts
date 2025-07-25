import { DependencyList, MutableRefObject, useRef } from 'react';
import isEqual from 'react-fast-compare';

export const useDeepMemoize = <T = DependencyList>(value: T): T => {
    const ref: MutableRefObject<T | undefined> = useRef();

    if (!isEqual(value, ref.current)) {
        ref.current = value;
    }

    return ref.current as T;
};
