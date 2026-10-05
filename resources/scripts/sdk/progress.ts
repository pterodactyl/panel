import { useContext } from 'react';
import { useQuery, type UseQueryResult } from '@tanstack/react-query';
import { retryUnlessClientError } from '@/api/queryRetry';
import { ExtensionContext } from '@/extensions/context';
import { extensionJobProgressQueryOptions } from '@/api/extensionProgress';
import type { ExtensionJobProgress } from '@/api/extensionTypes';
import { publicQueryOptions } from './queryOptions';
import type { SdkServerQueryOptions } from './server';

export type { ExtensionJobProgress } from '@/api/extensionTypes';

/** The key includes both the extension namespace and the subject. */
export function extensionProgressQueryOptions(
    extension: string,
    job: string,
    serverUuid?: string
): SdkServerQueryOptions<ExtensionJobProgress> {
    return publicQueryOptions(extensionJobProgressQueryOptions(extension, job, serverUuid));
}

/** Poll running jobs, stop terminal/expired jobs, and recover the latest snapshot on reconnect. */
export function useExtensionJobProgress(
    job: string | undefined,
    options: { serverUuid?: string } = {}
): UseQueryResult<ExtensionJobProgress, unknown> {
    const mount = useContext(ExtensionContext);
    if (!mount) throw new Error('Extension progress requires an extension mount.');
    return useQuery({
        ...extensionProgressQueryOptions(mount.extensionId, job ?? '', options.serverUuid),
        enabled: !!job,
        staleTime: 0,
        refetchOnReconnect: 'always',
        refetchInterval: (query) =>
            query.state.error || (query.state.data && query.state.data.status !== 'running') ? false : 1500,
        retry: retryUnlessClientError(2),
    });
}
