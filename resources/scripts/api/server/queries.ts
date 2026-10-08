import {
    partialMatchKey,
    useQuery,
    useMutation,
    useQueryClient,
    type QueryClient,
    type QueryFilters,
} from '@tanstack/react-query';
import { useServerRouteId } from '@/router/params';
import { useCallback } from 'react';
import { toast } from 'sonner';
import { invalidateGeneratedOperations } from '@/api/mutationUtils';
import { hasGeneratedOperationId, hasPathParam } from '@/api/queryKeyGuards';
import { isObject } from '@/lib/objects';

import {
    clientGetServerOptions,
    clientGetServerQueryKey,
    clientReinstallServerMutation,
    clientRenameServerMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type { ClientGetServerData, ClientGetServerResponse, ClientServerResource, Options } from '@/api/generated';
import { clientGetWebsocketCredentials, type ClientGetWebsocketCredentialsData } from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

const emptyPermissions: string[] = [];
const ownerPermissions = ['*'];

export type { Server } from '@/api/server/types';
import type { Server } from '@/api/server/types';
type WebsocketCredentials = {
    token: string;
    socket: string;
};

const serverInput = (id: string): Options<ClientGetServerData> => ({
    path: { server_uuid: id },
});

export const serverQueryKey = (id: string) => clientGetServerQueryKey(serverInput(id));

const identifiesServer = <TServer>(server: TServer, id: string): boolean =>
    isObject(server) &&
    'attributes' in server &&
    isObject(server.attributes) &&
    (('uuid' in server.attributes && server.attributes.uuid === id) ||
        ('identifier' in server.attributes && server.attributes.identifier === id));

/** Matches every cached copy of the server, keyed by uuid or short identifier. */
export const serverQueryFilters = (id: string): QueryFilters => ({
    predicate: ({ queryKey, state }) =>
        hasGeneratedOperationId(queryKey, 'clientGetServer') &&
        (partialMatchKey(queryKey, serverQueryKey(id)) || identifiesServer(state.data, id)),
});

/** Matches every client query for the server's own resources. */
export const serverResourceQueryFilters = (uuid: string): QueryFilters => ({
    predicate: ({ queryKey }) => hasPathParam(queryKey, 'server_uuid', uuid),
});

export const updateServerQueryData = (
    queryClient: QueryClient,
    id: string,
    updater: (server: ClientServerResource) => ClientServerResource
) => {
    const filters = serverQueryFilters(id);

    void queryClient.cancelQueries(filters);
    queryClient.setQueriesData<ClientGetServerResponse>(filters, (server) => (server ? updater(server) : server));
};

export const getServerWebsocketCredentials = async (uuid: string): Promise<WebsocketCredentials> => {
    const request: Options<ClientGetWebsocketCredentialsData> = { path: { server_uuid: uuid } };
    const response = await clientGetWebsocketCredentials(request);

    return {
        token: response.data?.data?.token ?? '',
        socket: response.data?.data?.socket ?? '',
    };
};

export const serverQueryOptions = (id: string) => clientGetServerOptions(serverInput(id));

export const useServerQuery = <TData = Server>(id: string, select?: (server: Server) => TData) =>
    useQuery({ ...serverQueryOptions(id), enabled: id.length > 0, select });

export const useCurrentServer = <TData = Server>(select?: (server: Server) => TData) => {
    const id = useServerRouteId();

    return useServerQuery(id ?? '', select).data;
};

const selectServerUuid = (server: Server) => server.attributes.uuid;
const selectServerIdentifier = (server: Server) => server.attributes.identifier;
const selectServerName = (server: Server) => server.attributes.name;

export const selectServerPermissions = (server: Server) =>
    server.meta?.is_server_owner ? ownerPermissions : (server.meta?.user_permissions ?? emptyPermissions);

export const useCurrentServerUuid = () => useCurrentServer(selectServerUuid);
export const useCurrentServerIdentifier = () => useCurrentServer(selectServerIdentifier);
export const useCurrentServerName = () => useCurrentServer(selectServerName);
export const useCurrentServerPermissions = () => useCurrentServer(selectServerPermissions) ?? emptyPermissions;

export const useUpdateCurrentServer = () => {
    const id = useServerRouteId();
    const queryClient = useQueryClient();

    return useCallback(
        (updater: (server: ClientServerResource) => ClientServerResource) => {
            if (!id) {
                return;
            }

            updateServerQueryData(queryClient, id, updater);
        },
        [id, queryClient]
    );
};

export const useRenameServer = (serverIdentifier: string) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientRenameServerMutation(),
        onSuccess: async (_data, variables) => {
            updateServerQueryData(queryClient, serverIdentifier, (server) => ({
                ...server,
                attributes: {
                    ...server.attributes,
                    name: variables.body.name,
                    description: variables.body.description ?? null,
                },
            }));
            await invalidateGeneratedOperations(queryClient, ['clientListClientServers']);
            toast.success('Server details updated');
        },
        onError: (error) => notifyHttpError(error, 'Unable to update server details'),
    });
};

export const useReinstallServer = () =>
    useMutation({
        ...clientReinstallServerMutation(),
        onSuccess: () => toast.success('Server reinstall started'),
        onError: (error) => notifyHttpError(error, 'Unable to reinstall server'),
    });
