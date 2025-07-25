import { Dispatch, SetStateAction, useEffect, useState } from 'react';

export function usePersistedState<S = undefined>(
    key: string,
    defaultValue: S
): [S | undefined, Dispatch<SetStateAction<S | undefined>>] {
    const [state, setState] = useState(() => {
        try {
            const item = localStorage.getItem(key);

            return JSON.parse(item || String(defaultValue));
        } catch (e) {
            console.warn('Failed to retrieve persisted value from store.', e);

            return defaultValue;
        }
    });

    useEffect(() => {
        localStorage.setItem(key, JSON.stringify(state));
    }, [key, state]);

    return [state, setState];
}
