import { useState } from 'react';
import { debounce } from 'debounce';

type DebounceFn<V> = ((value: V) => void) & { clear: () => void };

export default <S>(initial: S, interval?: number, immediate?: boolean): [S, DebounceFn<S>] => {
    const [state, setState] = useState<S>(initial);

    const debouncedSetState = debounce((v: S) => setState(v), interval, immediate);

    return [state, debouncedSetState];
};
