import { queryOptions, useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { fetchAllPages, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import { invalidateAdminNodeAllocations } from '@/api/admin/nodes/invalidation';
import {
    adminAttachServerMountMutation,
    adminCreateServerMutation,
    adminCreateServerDatabaseMutation,
    adminDeleteServerMutation,
    adminDeleteServerDatabaseMutation,
    adminDetachServerMountMutation,
    adminForceDeleteServerMutation,
    adminGetEggOptions,
    adminGetServerOptions,
    adminGetServerQueryKey,
    adminGetServerTransferProgressOptions,
    adminGetServerTransferProgressQueryKey,
    adminReinstallServerMutation,
    adminListServersOptions,
    adminListServersQueryKey,
    adminListServerDatabasesOptions,
    adminListServerDatabasesQueryKey,
    adminListServerMountsOptions,
    adminListServerMountsQueryKey,
    adminRotateServerDatabasePasswordMutation,
    adminToggleServerInstallStateMutation,
    adminTransferServerMutation,
    adminUpdateServerBuildMutation,
    adminUpdateServerDetailsMutation,
    adminUpdateServerStartupMutation,
    adminUpdateServerSuspensionMutation,
    adminListNodeAllocationsOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListNodeAllocations,
    type AdminAttachServerMountData,
    type AdminCreateServerDatabaseData,
    type AdminCreateServerData,
    type AdminDeleteServerData,
    type AdminDeleteServerDatabaseData,
    type AdminDetachServerMountData,
    type AdminForceDeleteServerData,
    type AdminGetEggData,
    type AdminGetEggResponse,
    type AdminEggResource,
    type AdminGetServerData,
    type AdminListNodeAllocationsData,
    type AdminListServersData,
    type AdminListServersResponse,
    type AdminMountResource,
    type AdminReinstallServerData,
    type AdminRotateServerDatabasePasswordData,
    type AdminServerDatabaseResource,
    type AdminServerResource,
    type AdminToggleServerInstallStateData,
    type AdminTransferServerData,
    type AdminUpdateServerBuildData,
    type AdminUpdateServerDetailsData,
    type AdminUpdateServerStartupData,
    type AdminUpdateServerSuspensionData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type ServersFilters = '*' | 'uuid' | 'uuidShort' | 'name' | 'external_id' | 'image' | 'node_id' | 'owner_id';
export type AdminServerSort = 'id' | 'uuid' | 'name' | 'created_at';
export type AdminServerListSort = ListSort<'name' | 'created_at'>;
type ServerInclude =
    | 'allocation'
    | 'allocations'
    | 'user'
    | 'subusers'
    | 'egg'
    | 'variables'
    | 'location'
    | 'node'
    | 'node.location'
    | 'databases';
type AdminServersQuery = NonNullable<AdminListServersData['query']>;

export interface AdminServersQueryParams extends QueryBuilderParams<ServersFilters, AdminServerSort> {
    include?: readonly ServerInclude[];
}

export type AdminServer = AdminServerResource;

export type EggForServer = AdminEggResource;

export type AdminServerMount = AdminMountResource;

export type AdminServerDatabase = AdminServerDatabaseResource;

export type CreateServerBody = AdminCreateServerData['body'];
export type ServerDetailsBody = AdminUpdateServerDetailsData['body'];
export type ServerBuildBody = AdminUpdateServerBuildData['body'];
export type ServerStartupBody = AdminUpdateServerStartupData['body'];
export type TransferServerBody = AdminTransferServerData['body'];
export type CreateServerDatabaseBody = AdminCreateServerDatabaseData['body'];

export const createAdminServerInput = (body: CreateServerBody): Options<AdminCreateServerData> => ({
    body,
});

export const updateAdminServerDetailsInput = (
    id: number,
    body: ServerDetailsBody
): Options<AdminUpdateServerDetailsData> => ({
    path: { server_admin_identifier: String(id) },
    body,
});

export const updateAdminServerBuildInput = (
    id: number,
    body: ServerBuildBody
): Options<AdminUpdateServerBuildData> => ({
    path: { server_admin_identifier: String(id) },
    body,
});

export const updateAdminServerStartupInput = (
    id: number,
    body: ServerStartupBody
): Options<AdminUpdateServerStartupData> => ({
    path: { server_admin_identifier: String(id) },
    body,
});

export const deleteAdminServerInput = (id: number, name?: string): Options<AdminDeleteServerData> => ({
    path: { server_admin_identifier: String(id) },
    meta: name ? { name } : undefined,
});

export const forceDeleteAdminServerInput = (id: number, name?: string): Options<AdminForceDeleteServerData> => ({
    path: { server_admin_identifier: String(id) },
    meta: name ? { name } : undefined,
});

export const transferAdminServerInput = (id: number, body: TransferServerBody): Options<AdminTransferServerData> => ({
    path: { server_admin_identifier: String(id) },
    body,
});

export const createAdminServerDatabaseInput = (
    id: number,
    body: CreateServerDatabaseBody
): Options<AdminCreateServerDatabaseData> => ({
    path: { server_admin_identifier: String(id) },
    body,
});

export const deleteAdminServerDatabaseInput = (
    serverId: number,
    databaseId: string,
    name?: string
): Options<AdminDeleteServerDatabaseData> => ({
    path: { server_admin_identifier: String(serverId), database_id: databaseId },
    meta: name ? { name } : undefined,
});

export const rotateAdminServerDatabasePasswordInput = (
    serverId: number,
    databaseId: string
): Options<AdminRotateServerDatabasePasswordData> => ({
    path: { server_admin_identifier: String(serverId), database_id: databaseId },
});

export const attachAdminServerMountInput = (
    serverId: number,
    mountId: number,
    name?: string
): Options<AdminAttachServerMountData> => ({
    path: { server_admin_identifier: String(serverId) },
    body: { mount_id: mountId },
    meta: name ? { name } : undefined,
});

export const detachAdminServerMountInput = (
    serverId: number,
    mountId: number,
    name?: string
): Options<AdminDetachServerMountData> => ({
    path: { server_admin_identifier: String(serverId), mount_id: mountId },
    meta: name ? { name } : undefined,
});

export const reinstallAdminServerInput = (id: number): Options<AdminReinstallServerData> => ({
    path: { server_admin_identifier: String(id) },
});

export const toggleAdminServerInstallStateInput = (id: number): Options<AdminToggleServerInstallStateData> => ({
    path: { server_admin_identifier: String(id) },
});

export const updateAdminServerSuspensionInput = (
    id: number,
    suspended: boolean
): Options<AdminUpdateServerSuspensionData> => ({
    path: { server_admin_identifier: String(id) },
    body: { suspended },
});

const adminServerListIncludes = ['user', 'node', 'allocation'] as const;
const adminServerDetailIncludes = ['allocations', 'user', 'node', 'node.location', 'egg', 'variables'] as const;

const numericFilterValue = (value: NonNullable<AdminServersQueryParams['filters']>['node_id' | 'owner_id']) => {
    if (value === undefined || value === null || value === '') {
        return undefined;
    }

    const normalized = Array.isArray(value) ? value[0] : value;
    const number = Number(normalized);

    return Number.isFinite(number) ? number : undefined;
};

const serverListSortFields = ['name', 'created_at'] as const;

export const adminServerListSorts = (sort?: AdminServerListSort): AdminServersQueryParams['sorts'] =>
    listSorts(serverListSortFields, sort);

const ownerIdFilterPattern = /^owner_id:(\d+)$/;

export const adminServerListQueryParams = (
    page: number,
    filter: string,
    sort?: AdminServerListSort
): AdminServersQueryParams => {
    const ownerId = ownerIdFilterPattern.exec(filter)?.[1];

    return {
        page,
        filters: ownerId ? { owner_id: Number(ownerId) } : { '*': filter },
        sorts: adminServerListSorts(sort),
        include: adminServerListIncludes,
    };
};

const toAdminServersQuery = ({
    include = [],
    filters = {},
    ...params
}: AdminServersQueryParams = {}): AdminServersQuery => {
    const { node_id: nodeIdFilter, owner_id: ownerIdFilter, ...textFilters } = filters;
    const query: AdminServersQuery = listQuery({ ...params, filters: textFilters });
    const nodeId = numericFilterValue(nodeIdFilter);
    const ownerId = numericFilterValue(ownerIdFilter);

    if (nodeId !== undefined) query['filter[node_id]'] = nodeId;
    if (ownerId !== undefined) query['filter[owner_id]'] = ownerId;
    if (include.length > 0) query.include = include.join(',');

    return query;
};

const adminServerInput = (id: number): Options<AdminGetServerData> => ({
    path: { server_admin_identifier: String(id) },
    query: { include: adminServerDetailIncludes.join(',') },
});

const adminEggForServerInput = (eggId: number): Options<AdminGetEggData> => ({
    path: { egg_id: eggId },
    query: { include: 'variables' },
});

const adminServerMountsInput = (id: number): Parameters<typeof adminListServerMountsOptions>[0] => ({
    path: { server_admin_identifier: String(id) },
});

const adminServerDatabasesInput = (id: number): Parameters<typeof adminListServerDatabasesOptions>[0] => ({
    path: { server_admin_identifier: String(id) },
    query: { include: 'password' },
});

const adminServerTransferInput = (id: number): Parameters<typeof adminGetServerTransferProgressOptions>[0] => ({
    path: { server_admin_identifier: String(id) },
});

export const adminServerDetailKey = (id: string | number) =>
    adminGetServerQueryKey({ path: { server_admin_identifier: String(id) } });

const cachedAdminServerNode = (queryClient: QueryClient, id: string | number): number | undefined => {
    const serverId = Number(id);
    const details = queryClient.getQueriesData<AdminServer>({ queryKey: adminServerDetailKey(serverId) });
    const lists = queryClient.getQueriesData<AdminListServersResponse>({ queryKey: adminListServersQueryKey() });
    const servers = [...details.map(([, server]) => server), ...lists.flatMap(([, list]) => list?.data ?? [])];

    return servers.find((server) => server?.attributes.id === serverId)?.attributes.node;
};

const invalidateAdminServer = async (queryClient: QueryClient, id: number) => {
    await Promise.all([
        queryClient.invalidateQueries({ queryKey: adminServerDetailKey(id) }),
        invalidateGeneratedOperations(queryClient, ['adminListServers']),
    ]);
};

export const useInvalidateAdminServer = (id: number) => {
    const queryClient = useQueryClient();

    return () => invalidateAdminServer(queryClient, id);
};

export const adminServersQueryOptions = (params: AdminServersQueryParams = {}) =>
    adminListServersOptions({ query: toAdminServersQuery(params) });

export const adminServerQueryOptions = (id: number) => ({
    ...adminGetServerOptions(adminServerInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminServersQueryOptions = Pick<ReturnType<typeof adminServersQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

type AdminNodeAllocationsInput = Options<AdminListNodeAllocationsData>;

const adminServerBuildAllocationsInput = (nodeId: number, serverId: number): AdminNodeAllocationsInput => ({
    path: { node_id: nodeId },
    query: { sort: 'id', per_page: MAX_PER_PAGE, 'filter[server_id]': String(serverId) },
});

const adminServerUnassignedAllocationsInput = (nodeId: number): AdminNodeAllocationsInput => ({
    path: { node_id: nodeId },
    query: { sort: 'id', per_page: MAX_PER_PAGE, 'filter[server_id]': '' },
});

// The allocation pickers need every matching allocation, so the list is loaded page by page.
const allAdminNodeAllocationsQueryOptions = (input: AdminNodeAllocationsInput) =>
    queryOptions({
        ...adminListNodeAllocationsOptions(input),
        queryFn: ({ signal }) =>
            fetchAllPages(
                async (page) =>
                    (await adminListNodeAllocations({ path: input.path, query: { ...input.query, page }, signal }))
                        .data,
                signal
            ),
    });

export const adminServerBuildAllocationsQueryOptions = (serverId: number, nodeId: number) =>
    allAdminNodeAllocationsQueryOptions(adminServerBuildAllocationsInput(nodeId, serverId));

export const adminServerUnassignedAllocationsQueryOptions = (nodeId: number) => ({
    ...allAdminNodeAllocationsQueryOptions(adminServerUnassignedAllocationsInput(nodeId)),
    enabled: nodeId > 0,
});

export const adminServerDatabasesQueryOptions = (id: number) => ({
    ...adminListServerDatabasesOptions(adminServerDatabasesInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

const adminServerDatabaseListQueryKey = (id: string | number) =>
    adminListServerDatabasesQueryKey({ path: { server_admin_identifier: String(id) } });

export const adminServerMountsQueryOptions = (id: number) => ({
    ...adminListServerMountsOptions(adminServerMountsInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

const adminServerMountListQueryKey = (id: string | number) =>
    adminListServerMountsQueryKey({ path: { server_admin_identifier: String(id) } });

const adminServerScopedKeys = (id: string | number) => [
    adminServerDetailKey(id),
    adminServerDatabaseListQueryKey(id),
    adminServerMountListQueryKey(id),
    adminGetServerTransferProgressQueryKey({ path: { server_admin_identifier: String(id) } }),
];

const adminServerTransferQueryOptions = (id: number) => ({
    ...adminGetServerTransferProgressOptions(adminServerTransferInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

export const useAdminServers = (params: AdminServersQueryParams = {}, options?: AdminServersQueryOptions) =>
    useQuery({ ...adminServersQueryOptions(params), ...options });

export const useAdminServer = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminServerQueryOptions(id), options));

export const useAdminServerBuildAllocations = (serverId: number, nodeId: number, options?: ResourceQueryOverrides) =>
    useQuery({ ...adminServerBuildAllocationsQueryOptions(serverId, nodeId), ...options });

export const useAdminServerUnassignedAllocations = (nodeId: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminServerUnassignedAllocationsQueryOptions(nodeId), options));

const adminEggForServerQueryOptions = (eggId: number) => ({
    ...adminGetEggOptions(adminEggForServerInput(eggId)),
    enabled: eggId > 0,
});

export const useAdminEggForServer = (eggId: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminEggForServerQueryOptions(eggId), options));

export const useFetchAdminEggForServer = () => {
    const queryClient = useQueryClient();

    return async (eggId: number): Promise<AdminGetEggResponse | null> => {
        if (eggId <= 0) {
            return null;
        }

        return queryClient.fetchQuery(adminGetEggOptions(adminEggForServerInput(eggId)));
    };
};

export const useAdminServerDatabases = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminServerDatabasesQueryOptions(id), options));

export const useAdminServerMounts = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminServerMountsQueryOptions(id), options));

export const useAdminServerTransferProgress = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminServerTransferQueryOptions(id), options));

export const useCreateAdminServer = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminCreateServerMutation(),
        onSuccess: async (server) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                invalidateAdminNodeAllocations(queryClient, [server.attributes.node]),
            ]);
            const messages = resourceMutationMessages('server', 'create', server.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('server', 'create').errorTitle),
    });
};

export const useUpdateAdminServerDetails = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateServerDetailsMutation(),
        onSuccess: async (server, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({
                    queryKey: adminServerDetailKey(variables.path.server_admin_identifier),
                }),
                invalidateGeneratedOperations(queryClient, ['adminGetExtensionFormValues']),
            ]);
            toast.success('Server details updated', { description: `${server.attributes.name} has been updated.` });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update server details'),
    });
};

export const useUpdateAdminServerBuild = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateServerBuildMutation(),
        onSuccess: async (server) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({ queryKey: adminServerDetailKey(server.attributes.id) }),
                invalidateAdminNodeAllocations(queryClient, [server.attributes.node]),
            ]);
            toast.success('Build configuration updated', {
                description: `${server.attributes.name} has updated build settings.`,
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update build configuration'),
    });
};

export const useUpdateAdminServerStartup = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateServerStartupMutation(),
        onSuccess: async (server, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({
                    queryKey: adminServerDetailKey(variables.path.server_admin_identifier),
                }),
            ]);
            toast.success('Startup configuration updated', {
                description: `${server.attributes.name} has updated startup settings.`,
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update startup configuration'),
    });
};

const onAdminServerDeleted = async (queryClient: QueryClient, id: string) => {
    const nodeId = cachedAdminServerNode(queryClient, id);
    for (const queryKey of adminServerScopedKeys(id)) {
        removeQueriesWhenUnobserved(queryClient, { queryKey });
    }

    await Promise.all([
        invalidateGeneratedOperations(queryClient, ['adminListServers']),
        invalidateAdminNodeAllocations(queryClient, nodeId === undefined ? undefined : [nodeId]),
    ]);
};

export const useDeleteAdminServer = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteServerMutation(),
        onSuccess: async (_data, variables) => {
            await onAdminServerDeleted(queryClient, variables.path.server_admin_identifier);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('server', 'delete', name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('server', 'delete').errorTitle),
    });
};

export const useForceDeleteAdminServer = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminForceDeleteServerMutation(),
        onSuccess: async (_data, variables) => {
            await onAdminServerDeleted(queryClient, variables.path.server_admin_identifier);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('server', 'delete', name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('server', 'delete').errorTitle),
    });
};

export const useTransferAdminServer = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminTransferServerMutation(),
        onSuccess: async (_data, variables) => {
            const id = Number(variables.path.server_admin_identifier);
            const sourceNodeId = cachedAdminServerNode(queryClient, id);
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({ queryKey: adminServerDetailKey(id) }),
                queryClient.invalidateQueries({
                    queryKey: adminGetServerTransferProgressQueryKey(adminServerTransferInput(id)),
                }),
                invalidateAdminNodeAllocations(
                    queryClient,
                    sourceNodeId === undefined ? undefined : [sourceNodeId, variables.body.node_id]
                ),
            ]);
            toast.success('Server transfer started', {
                description: 'The server will move to the selected node and allocation.',
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to transfer server'),
    });
};

export const useCreateAdminServerDatabase = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminCreateServerDatabaseMutation(),
        onSuccess: async (database, variables) => {
            await Promise.all([
                queryClient.invalidateQueries({
                    queryKey: adminServerDatabaseListQueryKey(variables.path.server_admin_identifier),
                }),
                invalidateGeneratedOperations(queryClient, ['adminListDatabaseHostDatabases']),
            ]);
            const messages = resourceMutationMessages('database', 'create', database.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('database', 'create').errorTitle),
    });
};

export const useDeleteAdminServerDatabase = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteServerDatabaseMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                queryClient.invalidateQueries({
                    queryKey: adminServerDatabaseListQueryKey(variables.path.server_admin_identifier),
                }),
                invalidateGeneratedOperations(queryClient, ['adminListDatabaseHostDatabases']),
            ]);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('database', 'delete', name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('database', 'delete').errorTitle),
    });
};

export const useRotateAdminServerDatabasePassword = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminRotateServerDatabasePasswordMutation(),
        onSuccess: async (database, variables) => {
            await queryClient.invalidateQueries({
                queryKey: adminServerDatabaseListQueryKey(variables.path.server_admin_identifier),
            });
            toast.success('Database password rotated', {
                description: `${database.attributes.name} has a new password.`,
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to rotate database password'),
    });
};

export const useAttachAdminServerMount = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminAttachServerMountMutation(),
        onSuccess: async (_data, variables) => {
            await queryClient.invalidateQueries({
                queryKey: adminServerMountListQueryKey(variables.path.server_admin_identifier),
            });
            const name = variables.meta?.name;
            toast.success('Mount attached', {
                description: name
                    ? `${name} has been mounted to this server.`
                    : 'The mount has been attached to this server.',
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to attach mount'),
    });
};

export const useDetachAdminServerMount = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDetachServerMountMutation(),
        onSuccess: async (_data, variables) => {
            await queryClient.invalidateQueries({
                queryKey: adminServerMountListQueryKey(variables.path.server_admin_identifier),
            });
            const name = variables.meta?.name;
            toast.success('Mount detached', {
                description: name
                    ? `${name} has been unmounted from this server.`
                    : 'The mount has been detached from this server.',
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to detach mount'),
    });
};

export const useReinstallAdminServer = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminReinstallServerMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({
                    queryKey: adminServerDetailKey(variables.path.server_admin_identifier),
                }),
            ]);
            toast.success('Server is being reinstalled.');
        },
        onError: (error) => notifyHttpError(error, 'Unable to reinstall server'),
    });
};

export const useToggleAdminServerInstallState = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminToggleServerInstallStateMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({
                    queryKey: adminServerDetailKey(variables.path.server_admin_identifier),
                }),
            ]);
            toast.success('Install status has been toggled.');
        },
        onError: (error) => notifyHttpError(error, 'Unable to toggle install status'),
    });
};

export const useUpdateAdminServerSuspension = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateServerSuspensionMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListServers']),
                queryClient.invalidateQueries({
                    queryKey: adminServerDetailKey(variables.path.server_admin_identifier),
                }),
            ]);
            toast.success(
                variables.body?.suspended === true ? 'Server has been suspended.' : 'Server has been unsuspended.'
            );
        },
        onError: (error, variables) =>
            notifyHttpError(
                error,
                variables.body?.suspended === true ? 'Unable to suspend server' : 'Unable to unsuspend server'
            ),
    });
};
