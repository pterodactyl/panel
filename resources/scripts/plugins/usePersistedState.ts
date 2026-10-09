import type { Dispatch, SetStateAction } from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';

type PersistedState<S> = {
    key: string;
    value: S | undefined;
};

const readPersistedValue = <S>(key: string, defaultValue: S): S | undefined => {
    try {
        const item = localStorage.getItem(key);

        return item === null ? defaultValue : JSON.parse(item);
    } catch (e) {
        console.warn('Failed to retrieve persisted value from store.', e);

        return defaultValue;
    }
};

export function usePersistedState<S = undefined>(
    key: string,
    defaultValue: S
): [S | undefined, Dispatch<SetStateAction<S | undefined>>] {
    const defaultValueRef = useRef(defaultValue);
    const [persisted, setPersisted] = useState<PersistedState<S>>(() => ({
        key,
        value: readPersistedValue(key, defaultValue),
    }));

    useEffect(() => {
        defaultValueRef.current = defaultValue;
    }, [defaultValue]);

    useEffect(() => {
        setPersisted((current) =>
            current.key === key ? current : { key, value: readPersistedValue(key, defaultValueRef.current) }
        );
    }, [key]);

    useEffect(() => {
        if (persisted.key !== key) {
            return;
        }

        const value = JSON.stringify(persisted.value);

        if (value === undefined) {
            localStorage.removeItem(key);
        } else {
            localStorage.setItem(key, value);
        }
    }, [key, persisted]);

    const setState = useCallback<Dispatch<SetStateAction<S | undefined>>>(
        (next) => {
            setPersisted((current) => {
                const currentValue =
                    current.key === key ? current.value : readPersistedValue(key, defaultValueRef.current);

                return {
                    key,
                    value:
                        next instanceof Function
                            ? (next as (current: S | undefined) => S | undefined)(currentValue)
                            : next,
                };
            });
        },
        [key]
    );

    const value = persisted.key === key ? persisted.value : readPersistedValue(key, defaultValueRef.current);

    return [value, setState];
}
