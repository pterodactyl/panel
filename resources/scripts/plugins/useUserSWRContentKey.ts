import { useStoreState } from '@/state/hooks';
import { useDeepCompareMemo } from '@/plugins/useDeepCompareMemo';

// eslint-disable-next-line @typescript-eslint/ban-types
export default (context: string | string[] | (string | number | null | {})[]) => {
    const uuid = useStoreState((state) => state.user.data?.uuid);
    const key = useDeepCompareMemo((): string => {
        return (Array.isArray(context) ? context : [context]).map((value) => JSON.stringify(value)).join(':');
    }, [context]);

    if (!key.trim().length) {
        throw new Error('Must provide a valid context key to "useUserSWRContextKey".');
    }

    return `swr::${uuid || 'unknown'}:${key.trim()}`;
};
