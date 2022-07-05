import { useDeepCompareMemo } from '@/plugins/useDeepCompareMemo';
import { ServerContext } from '@/state/server';
import { useStoreState } from '@/state/hooks';

// eslint-disable-next-line @typescript-eslint/ban-types
type Context = string | string[] | (string | number | null | {})[];

function useSWRKey(context: Context, prefix: string | null = null): string {
    const key = useDeepCompareMemo((): string => {
        return (Array.isArray(context) ? context : [context]).map((value) => JSON.stringify(value)).join(':');
    }, [context]);

    if (!key.trim().length) {
        throw new Error('Must provide a valid context key to "useSWRKey".');
    }

    return `swr::${prefix ? `${prefix}:` : ''}${key.trim()}`;
}

function useServerSWRKey(context: Context): string {
    const uuid = ServerContext.useStoreState((state) => state.server.data?.uuid);

    return useSWRKey(context, `server:${uuid}`);
}

function useUserSWRKey(context: Context): string {
    const uuid = useStoreState((state) => state.user.data?.uuid);

    return useSWRKey(context, `user:${uuid}`);
}

export default useSWRKey;
export { useServerSWRKey, useUserSWRKey };
