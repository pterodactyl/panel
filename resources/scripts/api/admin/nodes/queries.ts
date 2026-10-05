import {
    useQuery,
    useMutation,
    useQueries,
    useQueryClient,
    type QueryClient,
    type UseQueryResult,
} from '@tanstack/react-query';
import { toast } from 'sonner';

import { listQuery, listSorts, type ListSort } from '@/api/queryParameters';
import { allPagesQueryOptions, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import { adminServersQueryOptions } from '@/api/admin/servers/queries';
import { allAdminLocationsQueryOptions, type AdminLocation } from '@/api/admin/locations/queries';
import {
    adminNodeAllocationsKey,
    adminNodeDetailKey,
    invalidateAdminNodeAllocations,
} from '@/api/admin/nodes/invalidation';
import {
    adminBulkDeleteNodeAllocationsMutation,
    adminCreateNodeMutation,
    adminCreateNodeAllocationsMutation,
    adminCreateNodeDeployTokenMutation,
    adminDeleteNodeMutation,
    adminDeleteNodeAllocationIpBlockMutation,
    adminDeleteNodeAllocationMutation,
    adminGetNodeOptions,
    adminGetNodeConfigurationOptions,
    adminGetNodeConfigurationQueryKey,
    adminGetNodeSystemInformationOptions,
    adminGetNodeSystemInformationQueryKey,
    adminGetNodeUtilizationOptions,
    adminGetNodeUtilizationQueryKey,
    adminListNodeAllocationsOptions,
    adminListNodesOptions,
    adminListNodesQueryKey,
    adminUpdateNodeMutation,
    adminUpdateNodeAllocationMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListNodes,
    type AdminAllocationResource,
    type AdminBulkDeleteNodeAllocationsData,
    type AdminCreateNodeData,
    type AdminCreateNodeAllocationsData,
    type AdminCreateNodeDeployTokenData,
    type AdminDeleteNodeData,
    type AdminDeleteNodeAllocationData,
    type AdminDeleteNodeAllocationIpBlockData,
    type AdminGetNodeUtilizationResponse,
    type AdminNodeDeployTokenResponse,
    type AdminNodeResource,
    type AdminListNodesData,
    type AdminListLocationsResponse,
    type AdminListNodesResponse,
    type AdminUpdateNodeData,
    type AdminUpdateNodeAllocationData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminNode = AdminNodeResource;
export type AdminNodeSort = 'id' | 'uuid' | 'name' | 'memory' | 'disk' | 'created_at';
export type AdminNodeListSort = ListSort<'name' | 'memory' | 'disk' | 'created_at'>;

export interface NodeValues {
    name: string;
    description: string;
    locationId: number;
    public: boolean;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    maintenanceMode: boolean;
    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;
    uploadSize: number;
    daemonListen: number;
    daemonSftp: number;
    daemonBase: string;
}

export type AdminAllocation = AdminAllocationResource;

export interface CreateAllocationValues {
    ip: string;
    alias: string;
    ports: string[];
}

export type DeployToken = AdminNodeDeployTokenResponse;
export type NodeUtilization = AdminGetNodeUtilizationResponse;

export interface PickerNode {
    id: number;
    locationId: number;
    name: string;
    fqdn: string;
}

export interface LocationWithNodes {
    location: AdminLocation;
    nodes: PickerNode[];
}

export interface AdminNodesQueryParams {
    page?: number;
    filters?: Partial<Record<'uuid' | 'name' | 'fqdn', string>>;
    sorts?: Partial<Record<AdminNodeSort, -1 | 1 | 'asc' | 'desc' | null>>;
}

const adminNodeListInclude = 'location' satisfies NonNullable<NonNullable<AdminListNodesData['query']>['include']>;

const allAdminNodesInput = {
    query: { sort: 'id', per_page: MAX_PER_PAGE, include: adminNodeListInclude },
} satisfies Options<AdminListNodesData>;

const nodeListSortFields = ['name', 'memory', 'disk', 'created_at'] as const;

export const adminNodeListSorts = (sort?: AdminNodeListSort): AdminNodesQueryParams['sorts'] =>
    listSorts(nodeListSortFields, sort);

export const adminNodeListQueryParams = (
    page: number,
    filter: string,
    sort?: AdminNodeListSort
): AdminNodesQueryParams => ({
    page,
    filters: { name: filter },
    sorts: adminNodeListSorts(sort),
});

const nodeValuesToCreateBody = (values: NodeValues): AdminCreateNodeData['body'] => ({
    name: values.name,
    description: values.description,
    location_id: values.locationId,
    public: values.public,
    fqdn: values.fqdn,
    scheme: values.scheme,
    behind_proxy: values.behindProxy,
    maintenance_mode: values.maintenanceMode,
    memory: values.memory,
    memory_overallocate: values.memoryOverallocate,
    disk: values.disk,
    disk_overallocate: values.diskOverallocate,
    upload_size: values.uploadSize,
    daemon_listen: values.daemonListen,
    daemon_sftp: values.daemonSftp,
    daemon_base: values.daemonBase,
});

const nodeValuesToUpdateBody = (values: NodeValues, resetSecret = false): AdminUpdateNodeData['body'] => ({
    ...nodeValuesToCreateBody(values),
    reset_secret: resetSecret,
});

export const createAdminNodeInput = (values: NodeValues): Options<AdminCreateNodeData> => ({
    body: nodeValuesToCreateBody(values),
});

export const updateAdminNodeInput = (
    id: number,
    values: NodeValues,
    resetSecret = false
): Options<AdminUpdateNodeData> => ({
    path: { id },
    body: nodeValuesToUpdateBody(values, resetSecret),
});

export const deleteAdminNodeInput = (id: number, name?: string): Options<AdminDeleteNodeData> => ({
    path: { node_id: id },
    meta: name ? { name } : undefined,
});

export const generateAdminNodeDeployTokenInput = (id: number): Options<AdminCreateNodeDeployTokenData> => ({
    path: { node_id: id },
});

export const createAdminNodeAllocationsInput = (
    nodeId: number,
    values: CreateAllocationValues
): Options<AdminCreateNodeAllocationsData> => ({
    path: { node_id: nodeId },
    body: {
        ip: [values.ip],
        alias: values.alias,
        ports: values.ports,
    },
});

export const updateAdminNodeAllocationAliasInput = (
    nodeId: number,
    allocationId: number,
    alias: string
): Options<AdminUpdateNodeAllocationData> => ({
    path: { node_id: nodeId, id: allocationId },
    body: { alias },
});

export const deleteAdminNodeAllocationInput = (
    nodeId: number,
    allocationId: number
): Options<AdminDeleteNodeAllocationData> => ({
    path: { node_id: nodeId, allocation_id: allocationId },
});

export const bulkDeleteAdminNodeAllocationsInput = (
    nodeId: number,
    allocationIds: number[]
): Options<AdminBulkDeleteNodeAllocationsData> => ({
    path: { node_id: nodeId },
    body: { ids: allocationIds },
});

export const deleteAdminNodeIpBlockAllocationsInput = (
    nodeId: number,
    ip: string
): Options<AdminDeleteNodeAllocationIpBlockData> => ({
    path: { node_id: nodeId },
    body: { ip },
});

const invalidateAdminNode = async (queryClient: QueryClient, id: number) => {
    await Promise.all([
        queryClient.invalidateQueries({ queryKey: adminNodeDetailKey(id) }),
        invalidateGeneratedOperations(queryClient, ['adminListNodes']),
    ]);
};

const adminNodeScopedKeys = (nodeId: number) => [
    adminNodeDetailKey(nodeId),
    adminNodeAllocationsKey(nodeId),
    adminGetNodeConfigurationQueryKey({ path: { node_id: nodeId } }),
    adminGetNodeSystemInformationQueryKey({ path: { node_id: nodeId } }),
    adminGetNodeUtilizationQueryKey({ path: { node_id: nodeId } }),
];

const groupNodesByLocation = (
    nodesResponse: AdminListNodesResponse,
    locations: AdminLocation[]
): LocationWithNodes[] => {
    const nodes: PickerNode[] = nodesResponse.data.map(({ attributes }) => ({
        id: attributes.id,
        locationId: attributes.location_id,
        name: attributes.name,
        fqdn: attributes.fqdn,
    }));

    return locations
        .map((location: AdminLocation) => ({
            location,
            nodes: nodes.filter((node) => node.locationId === location.attributes.id),
        }))
        .filter((group: LocationWithNodes) => group.nodes.length > 0);
};

export const allAdminNodesQueryOptions = () =>
    allPagesQueryOptions(adminListNodesQueryKey(allAdminNodesInput), (page, signal) =>
        adminListNodes({ query: { ...allAdminNodesInput.query, page }, signal }).then(({ data }) => data)
    );

export const adminNodesQueryOptions = (params: AdminNodesQueryParams = {}) =>
    adminListNodesOptions({ query: { ...listQuery(params), include: adminNodeListInclude } });

export const adminNodeQueryOptions = (id: number) => ({
    ...adminGetNodeOptions({
        path: { node_id: id },
        query: { include: 'location' },
    }),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminNodesQueryOptions = Pick<ReturnType<typeof adminNodesQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

export const adminNodeConfigurationQueryOptions = (id: number) => ({
    ...adminGetNodeConfigurationOptions({ path: { node_id: id } }),
    enabled: Number.isInteger(id) && id > 0,
});

const adminNodeSystemInformationQueryOptions = (id: number) => ({
    ...adminGetNodeSystemInformationOptions({ path: { node_id: id } }),
    enabled: Number.isInteger(id) && id > 0,
});

const adminNodeUtilizationQueryOptions = (id: number) => ({
    ...adminGetNodeUtilizationOptions({ path: { node_id: id } }),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminNodeSystemInformationQueryOptions = ResourceQueryOverrides & {
    refetchInterval?: number | false;
    retry?: boolean | number;
};

export const adminNodeServersQueryOptions = (id: number, page: number) => ({
    ...adminServersQueryOptions({ page, filters: { node_id: id }, include: ['user', 'egg'] }),
    enabled: Number.isInteger(id) && id > 0,
});

export const adminNodeAllocationsQueryOptions = (id: number, page: number) => ({
    ...adminListNodeAllocationsOptions({
        path: { node_id: id },
        query: { page },
    }),
    enabled: Number.isInteger(id) && id > 0,
});

export const useInvalidateAdminNode = (id: number) => {
    const queryClient = useQueryClient();

    return () => invalidateAdminNode(queryClient, id);
};

export const useAdminNodes = (params: AdminNodesQueryParams = {}, options?: AdminNodesQueryOptions) =>
    useQuery({ ...adminNodesQueryOptions(params), ...options });

export const useAdminNode = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminNodeQueryOptions(id), options));

const combineNodesByLocation = ([nodes, locations]: [
    UseQueryResult<AdminListNodesResponse>,
    UseQueryResult<AdminListLocationsResponse>,
]) => ({
    data: nodes.data && locations.data ? groupNodesByLocation(nodes.data, locations.data.data) : undefined,
    isLoading: nodes.isLoading || locations.isLoading,
    isFetching: nodes.isFetching || locations.isFetching,
    isError: nodes.isError || locations.isError,
    error: nodes.error ?? locations.error,
    refetch: () => Promise.all([nodes.refetch(), locations.refetch()]),
});

export const useAdminNodesGroupedByLocation = (options?: ResourceQueryOverrides) =>
    useQueries({
        queries: [
            { ...allAdminNodesQueryOptions(), enabled: options?.enabled },
            { ...allAdminLocationsQueryOptions(), enabled: options?.enabled },
        ],
        combine: combineNodesByLocation,
    });

export const useAdminNodeConfiguration = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminNodeConfigurationQueryOptions(id), options));

export const useAdminNodeSystemInformation = (id: number, options?: AdminNodeSystemInformationQueryOptions) =>
    useQuery(withQueryOverrides(adminNodeSystemInformationQueryOptions(id), options));

export const useAdminNodeUtilization = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminNodeUtilizationQueryOptions(id), options));

export const useAdminNodeServers = (id: number, page: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminNodeServersQueryOptions(id, page), options));

export const useAdminNodeAllocations = (id: number, page: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminNodeAllocationsQueryOptions(id, page), options));

export const useCreateAdminNode = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminCreateNodeMutation(),
        onSuccess: async (node) => {
            await invalidateGeneratedOperations(queryClient, ['adminListNodes']);
            const messages = resourceMutationMessages('node', 'create', node.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('node', 'create').errorTitle),
    });
};

export const useUpdateAdminNode = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateNodeMutation(),
        onSuccess: async (node, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListNodes']),
                queryClient.invalidateQueries({ queryKey: adminNodeDetailKey(variables.path.id) }),
                queryClient.invalidateQueries({
                    queryKey: adminGetNodeConfigurationQueryKey({ path: { node_id: variables.path.id } }),
                }),
            ]);
            const messages = resourceMutationMessages('node', 'update', node.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('node', 'update').errorTitle),
    });
};

export const useDeleteAdminNode = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteNodeMutation(),
        onSuccess: async (_data, variables) => {
            for (const queryKey of adminNodeScopedKeys(variables.path.node_id)) {
                removeQueriesWhenUnobserved(queryClient, { queryKey });
            }
            await invalidateGeneratedOperations(queryClient, ['adminListNodes']);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('node', 'delete', name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('node', 'delete').errorTitle),
    });
};

export const useGenerateAdminNodeDeployToken = () =>
    useMutation({
        ...adminCreateNodeDeployTokenMutation(),
        onError: (error) => notifyHttpError(error, 'Unable to generate deploy token'),
    });

export const useCreateAdminNodeAllocations = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminCreateNodeAllocationsMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateAdminNodeAllocations(queryClient, [variables.path.node_id]);
            toast.success('Allocations assigned', { description: 'New allocations have been assigned.' });
        },
        onError: (error) => notifyHttpError(error, 'Unable to assign allocations'),
    });
};

export const useUpdateAdminNodeAllocationAlias = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateNodeAllocationMutation(),
        onSuccess: (_data, variables) => invalidateAdminNodeAllocations(queryClient, [variables.path.node_id]),
        onError: (error) => notifyHttpError(error, 'Unable to update allocation alias'),
    });
};

export const useDeleteAdminNodeAllocation = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteNodeAllocationMutation(),
        onSuccess: (_data, variables) => invalidateAdminNodeAllocations(queryClient, [variables.path.node_id]),
        onError: (error) => notifyHttpError(error, 'Unable to delete allocation'),
    });
};

export const useBulkDeleteAdminNodeAllocations = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminBulkDeleteNodeAllocationsMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateAdminNodeAllocations(queryClient, [variables.path.node_id]);
            toast.success('Allocations deleted', { description: 'Selected allocations have been deleted.' });
        },
        onError: (error) => notifyHttpError(error, 'Unable to delete allocations'),
    });
};

export const useDeleteAdminNodeIpBlockAllocations = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteNodeAllocationIpBlockMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateAdminNodeAllocations(queryClient, [variables.path.node_id]);
            toast.success('Allocations deleted', {
                description: `All unassigned allocations for ${variables.body.ip} have been deleted.`,
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to delete allocations'),
    });
};
