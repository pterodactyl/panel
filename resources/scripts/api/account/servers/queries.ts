import { useQuery } from '@tanstack/react-query';

import {
    clientGetServerResourcesOptions,
    clientListClientServersOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    ClientAllocationResource,
    ClientStatsAttributes,
    ClientGetServerResourcesData,
    ClientGetServerResourcesResponse,
    ClientListClientServersData,
    ClientServerResource,
    Options,
} from '@/api/generated';

type ClientServerListQuery = NonNullable<ClientListClientServersData['query']>;
type ClientServerListType = ClientServerListQuery['type'];
export type AccountServer = ClientServerResource;
export type ServerPowerState = ClientStatsAttributes['current_state'];
export type { ClientAllocationResource, ClientStatsAttributes };

type ServerListParams = {
    page?: number;
    query?: string;
    type?: ClientServerListType;
};

const clientServersInput = ({ page, query, type }: ServerListParams = {}): Options<ClientListClientServersData> => {
    const queryParameters: ClientServerListQuery = { page, type };

    if (query) {
        queryParameters['filter[*]'] = query;
    }

    return { query: queryParameters };
};

export const accountServersQueryOptions = ({ page = 1, type }: ServerListParams = {}) =>
    clientListClientServersOptions(clientServersInput({ page, type }));

export const useAccountServers = (params: ServerListParams = {}) => useQuery(accountServersQueryOptions(params));

const accountServerSearchQueryOptions = ({ query = '', type }: ServerListParams = {}) =>
    clientListClientServersOptions(clientServersInput({ query, type }));

export const useAccountServerSearch = (params: ServerListParams = {}, enabled = true) =>
    useQuery({ ...accountServerSearchQueryOptions(params), enabled });

const serverResourcesInput = (uuid: string): Options<ClientGetServerResourcesData> => ({
    path: { server_uuid: uuid },
});

export const serverResourceUsageQueryOptions = (
    uuid: string
): ReturnType<typeof clientGetServerResourcesOptions> & { retry: false } => ({
    ...clientGetServerResourcesOptions(serverResourcesInput(uuid)),
    retry: false,
});

/** Wings refreshes usage at most every 20 seconds. */
export const serverResourceUsageRefetchInterval = (query: {
    state: { data?: ClientGetServerResourcesResponse };
}): number | false => (query.state.data?.attributes.is_suspended ? false : 30000);

export const useServerResourceUsage = (uuid: string, enabled = true) =>
    useQuery({
        ...serverResourceUsageQueryOptions(uuid),
        enabled: enabled && uuid.length > 0,
        refetchInterval: serverResourceUsageRefetchInterval,
    });
