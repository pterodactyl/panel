import isEqual from 'react-fast-compare';
import { DependencyList, MutableRefObject, useRef } from 'react';

export const useDeepMemoize = <T = DependencyList>(value: T): T => {
    const ref: MutableRefObject<T | undefined> = useRef();

    if (!isEqual(value, ref.current)) {
        ref.current = value;
    }

    return ref.current as T;
};
