import type { QueryFilters, QueryKey } from '@tanstack/react-query';
import {
    clientListFilesQueryKey,
    clientGetFileContentsQueryKey,
    clientGetStartupConfigurationQueryKey,
    clientListServerBackupsQueryKey,
    clientGetServerResourcesQueryKey,
} from '@/api/generated/@tanstack/react-query.gen';
import { serverQueryFilters } from '@/api/server/queries';

export type ServerDataDomain = 'server' | 'files' | 'fileContent' | 'startup' | 'backups' | 'resources';
const domainKeys = {
    files: (id: string) => clientListFilesQueryKey({ path: { server_uuid: id } }),
    fileContent: (id: string) => {
        const [{ query: _query, ...key }] = clientGetFileContentsQueryKey({
            path: { server_uuid: id },
            query: { file: '' },
        });

        return [key];
    },
    startup: (id: string) => clientGetStartupConfigurationQueryKey({ path: { server_uuid: id } }),
    backups: (id: string) => clientListServerBackupsQueryKey({ path: { server_uuid: id } }),
    resources: (id: string) => clientGetServerResourcesQueryKey({ path: { server_uuid: id } }),
} satisfies Record<Exclude<ServerDataDomain, 'server'>, (id: string) => QueryKey>;

export function serverDomainQueryFilters(id: string, domain: ServerDataDomain): QueryFilters {
    return domain === 'server' ? serverQueryFilters(id) : { queryKey: domainKeys[domain](id) };
}
