import type { QueryFunction, QueryKey, UseQueryOptions } from '@tanstack/react-query';
import { queryOptions, skipToken } from '@tanstack/react-query';
import type { SdkServerQueryOptions } from './server';

export function publicQueryOptions<TData, TError, TKey extends QueryKey>(
    options: Pick<UseQueryOptions<TData, TError, TData, TKey>, 'queryKey' | 'queryFn'>,
    retry?: SdkServerQueryOptions<TData>['retry']
): SdkServerQueryOptions<TData> {
    const fetch = options.queryFn;

    if (!fetch || fetch === skipToken) {
        throw new Error('The panel query has no fetcher.');
    }

    const queryFn: QueryFunction<TData> = (context) => fetch({ ...context, queryKey: options.queryKey });
    const { queryKey } = queryOptions<TData, unknown, TData, QueryKey>({
        queryKey: options.queryKey,
        queryFn,
    });

    return retry === undefined ? { queryKey, queryFn } : { queryKey, queryFn, retry };
}
