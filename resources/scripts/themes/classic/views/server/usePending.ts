import { useCallback, useState } from 'react';

/** Whether any tracked action is still running; rejections are swallowed, the panel having reported them. */
export function usePending(): [boolean, (action: Promise<unknown>) => void] {
    const [count, setCount] = useState(0);
    const track = useCallback((action: Promise<unknown>) => {
        setCount((current) => current + 1);
        void action.catch(() => {}).finally(() => setCount((current) => current - 1));
    }, []);

    return [count > 0, track];
}
