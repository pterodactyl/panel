import type { DataTag, QueryFunction, QueryKey, UseQueryResult } from '@tanstack/react-query';
import { useQuery } from '@tanstack/react-query';
import { queryClient } from '@/api/queryClient';
import { serverQueryOptions as panelServerOptions } from '@/api/server/queries';
import {
    serverFilesQueryOptions as panelFilesOptions,
    serverFileContentQueryOptions as panelFileContentOptions,
} from '@/api/server/files/queries';
import { serverStartupQueryOptions as panelStartupOptions } from '@/api/server/startup/queries';
import { serverBackupsQueryOptions as panelBackupsOptions } from '@/api/server/backups/queries';
import {
    serverResourceUsageQueryOptions as panelResourcesOptions,
    serverResourceUsageRefetchInterval,
} from '@/api/account/servers/queries';
import { serverLogsQueryOptions as panelLogsOptions } from '@/api/server/logs/queries';
import { serverDomainQueryFilters, type ServerDataDomain } from '@/api/server/queryDomains';
import { publicQueryOptions } from './queryOptions';
import type {
    ClientGetServerResponse,
    ClientGetServerResourcesResponse,
    ClientGetServerLogsResponse,
    ClientListFilesResponse,
    ClientGetStartupConfigurationResponse,
    ClientListServerBackupsResponse,
} from '@/api/extensionTypes';

export type SdkServerFiles = ClientListFilesResponse;
export type SdkServerStartup = ClientGetStartupConfigurationResponse;
export type SdkServerBackups = ClientListServerBackupsResponse;
export type SdkServerResources = ClientGetServerResourcesResponse;
export type SdkServerLogs = ClientGetServerLogsResponse;
export type SdkFile = SdkServerFiles['data'][number];
export type SdkBackup = SdkServerBackups['data'][number];
export interface SdkServerQueryOptions<TData> {
    queryKey: DataTag<QueryKey, TData, unknown>;
    queryFn: QueryFunction<TData>;
    retry?: boolean | number | ((failureCount: number, error: Error) => boolean);
}

export function serverQueryOptions(id: string): SdkServerQueryOptions<ClientGetServerResponse> {
    return publicQueryOptions(panelServerOptions(id));
}

export function serverFilesQueryOptions(id: string, directory: string): SdkServerQueryOptions<SdkServerFiles> {
    return publicQueryOptions(panelFilesOptions(id, directory));
}

export function serverFileContentQueryOptions(id: string, file: string): SdkServerQueryOptions<string> {
    return publicQueryOptions(panelFileContentOptions(id, file));
}

export function serverStartupQueryOptions(id: string): SdkServerQueryOptions<SdkServerStartup> {
    const options = panelStartupOptions(id);

    return publicQueryOptions(options, options.retry);
}

export function serverBackupsQueryOptions(id: string, page = 1): SdkServerQueryOptions<SdkServerBackups> {
    return publicQueryOptions(panelBackupsOptions(id, page));
}

/** Power state and resource usage, in the cache entry the dashboard's server cards poll. */
export function serverResourcesQueryOptions(id: string): SdkServerQueryOptions<SdkServerResources> {
    const options = panelResourcesOptions(id);

    return publicQueryOptions(options, options.retry);
}

/** Recent console output, oldest first; `lines` is clamped to 1-100 and needs `websocket.connect`. */
export function serverLogsQueryOptions(id: string, lines?: number): SdkServerQueryOptions<SdkServerLogs> {
    return publicQueryOptions(panelLogsOptions(id, lines));
}

export function useServerFiles<TData = SdkServerFiles>(
    id: string,
    directory: string,
    select?: (data: SdkServerFiles) => TData
): UseQueryResult<TData, unknown> {
    return useQuery({ ...serverFilesQueryOptions(id, directory), enabled: !!id, select });
}

export function useServerFileContent(id: string, file: string): UseQueryResult<string, unknown> {
    return useQuery({ ...serverFileContentQueryOptions(id, file), enabled: !!id && !!file });
}

export function useServerStartup<TData = SdkServerStartup>(
    id: string,
    select?: (data: SdkServerStartup) => TData
): UseQueryResult<TData, unknown> {
    return useQuery({ ...serverStartupQueryOptions(id), enabled: !!id, select });
}

export function useServerBackups<TData = SdkServerBackups>(
    id: string,
    page = 1,
    select?: (data: SdkServerBackups) => TData
): UseQueryResult<TData, unknown> {
    return useQuery({ ...serverBackupsQueryOptions(id, page), enabled: !!id, select });
}

/** Polls like the dashboard. Inside the server area, the "stats" and "status" websocket events are live. */
export function useServerResources<TData = SdkServerResources>(
    id: string,
    select?: (data: SdkServerResources) => TData
): UseQueryResult<TData, unknown> {
    return useQuery({
        ...serverResourcesQueryOptions(id),
        enabled: !!id,
        select,
        refetchInterval: serverResourceUsageRefetchInterval,
    });
}

export type SdkServerDataDomain = ServerDataDomain;

/** Return this promise from extension mutation callbacks to await the host's refetches. */
export async function invalidateServerData(id: string, domains: readonly SdkServerDataDomain[]): Promise<void> {
    await Promise.all(
        [...new Set(domains)].map((domain) => queryClient.invalidateQueries(serverDomainQueryFilters(id, domain)))
    );
}
