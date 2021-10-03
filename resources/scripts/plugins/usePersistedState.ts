import { Dispatch, SetStateAction, useEffect, useState } from 'react';

export function usePersistedState<S extends any = undefined> (key: string, defaultValue: S): [ S, Dispatch<SetStateAction<S>> ] {
    const [ state, setState ] = useState(
        () => {
            try {
                const item = localStorage.getItem(key);

                if (item === null) {
                    return defaultValue;
                }

                return JSON.parse(item || String(defaultValue));
            } catch (e) {
                console.warn('Failed to retrieve persisted value from store.', e);

                return defaultValue;
            }
        },
    );

    useEffect(() => {
        localStorage.setItem(key, JSON.stringify(state));
    }, [ key, state ]);

    return [ state, setState ];
}
