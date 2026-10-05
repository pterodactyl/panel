import { queryOptions, type QueryFunction } from '@tanstack/react-query';
import { clientGetExtensionJobProgress, clientGetServerExtensionJobProgress } from './generated/sdk.gen';
import type { ExtensionJobProgress } from './extensionTypes';

export function extensionJobProgressQueryOptions(extension: string, job: string, serverUuid?: string) {
    const queryFn: QueryFunction<ExtensionJobProgress> = async ({ signal }) => {
        const result = serverUuid
            ? await clientGetServerExtensionJobProgress({ path: { extension, job, server_uuid: serverUuid }, signal })
            : await clientGetExtensionJobProgress({ path: { extension, job }, signal });
        return result.data.data;
    };
    const options = queryOptions({
        queryKey: ['extension-progress', extension, serverUuid ?? null, job],
        queryFn,
    });
    return { queryKey: options.queryKey, queryFn };
}
