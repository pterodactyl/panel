import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import {
    adminAttachEggsToMountMutation,
    adminAttachNodesToMountMutation,
    adminCreateMountMutation,
    adminDeleteMountMutation,
    adminDetachEggFromMountMutation,
    adminDetachNodeFromMountMutation,
    adminGetMountOptions,
    adminGetMountQueryKey,
    adminListMountsOptions,
    adminUpdateMountMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    type AdminAttachEggsToMountData,
    type AdminAttachNodesToMountData,
    type AdminCreateMountData,
    type AdminDeleteMountData,
    type AdminDetachEggFromMountData,
    type AdminDetachNodeFromMountData,
    type AdminGetMountData,
    type AdminMountResource,
    type AdminUpdateMountData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

type MountsFilters = 'name';
type MountsSorts = 'id' | 'name';
type MountInclude = NonNullable<NonNullable<AdminGetMountData['query']>['include']>;

export type AdminMountsQueryParams = QueryBuilderParams<MountsFilters, MountsSorts>;
export type AdminMountListSort = ListSort<'name'>;

export type AdminMount = AdminMountResource;

export interface MountValues {
    name: string;
    description: string;
    source: string;
    target: string;
    readOnly: boolean;
    userMountable: boolean;
}

export type AdminMountEgg = Extract<
    NonNullable<NonNullable<AdminMountResource['attributes']['relationships']>['eggs']>,
    { data: unknown[] }
>['data'][number];
export type AdminMountNode = Extract<
    NonNullable<NonNullable<AdminMountResource['attributes']['relationships']>['nodes']>,
    { data: unknown[] }
>['data'][number];
export type AdminMountWithRelations = AdminMountResource;

const mountInclude = 'eggs,nodes,servers' satisfies MountInclude;

const mountListSortFields = ['name'] as const;

export const adminMountListSorts = (sort?: AdminMountListSort): AdminMountsQueryParams['sorts'] =>
    listSorts(mountListSortFields, sort);

export const adminMountListQueryParams = (
    page: number,
    filter: string,
    sort?: AdminMountListSort
): AdminMountsQueryParams => ({
    page,
    filters: { name: filter },
    sorts: adminMountListSorts(sort),
});

const toMountBody = (values: MountValues): AdminCreateMountData['body'] => ({
    name: values.name,
    description: values.description,
    source: values.source,
    target: values.target,
    read_only: values.readOnly,
    user_mountable: values.userMountable,
});

export const createAdminMountInput = (values: MountValues): Options<AdminCreateMountData> => ({
    body: toMountBody(values),
});

export const updateAdminMountInput = (id: number, values: MountValues): Options<AdminUpdateMountData> => ({
    path: { id },
    body: toMountBody(values),
});

export const deleteAdminMountInput = (id: number, name?: string): Options<AdminDeleteMountData> => ({
    path: { mount_id: id },
    meta: name ? { name } : undefined,
});

export const attachAdminMountEggsInput = (id: number, eggs: number[]): Options<AdminAttachEggsToMountData> => ({
    path: { mount_id: id },
    body: { eggs },
    query: { include: mountInclude },
});

export const attachAdminMountNodesInput = (id: number, nodes: number[]): Options<AdminAttachNodesToMountData> => ({
    path: { mount_id: id },
    body: { nodes },
    query: { include: mountInclude },
});

export const detachAdminMountEggInput = (
    id: number,
    eggId: number,
    eggName?: string
): Options<AdminDetachEggFromMountData> => ({
    path: { mount_id: id, egg_id: eggId },
    meta: eggName ? { name: eggName } : undefined,
});

export const detachAdminMountNodeInput = (
    id: number,
    nodeId: number,
    nodeName?: string
): Options<AdminDetachNodeFromMountData> => ({
    path: { mount_id: id, node_id: nodeId },
    meta: nodeName ? { name: nodeName } : undefined,
});

const adminMountInput = (id: number): Options<AdminGetMountData> => ({
    path: { mount_id: id },
    query: { include: mountInclude },
});

const adminMountQueryKey = (id: number) => adminGetMountQueryKey(adminMountInput(id));

export const adminMountDetailKey = (id: number) => adminGetMountQueryKey({ path: { mount_id: id } });

export const adminMountsQueryOptions = (params: AdminMountsQueryParams = {}) =>
    adminListMountsOptions({ query: listQuery(params) });

export const adminMountQueryOptions = (id: number) => ({
    ...adminGetMountOptions(adminMountInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminMountsQueryOptions = Pick<ReturnType<typeof adminMountsQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

export const useAdminMounts = (params: AdminMountsQueryParams = {}, options?: AdminMountsQueryOptions) =>
    useQuery({ ...adminMountsQueryOptions(params), ...options });

export const useAdminMount = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminMountQueryOptions(id), options));

export const useCreateAdminMount = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminCreateMountMutation(),
        onSuccess: async (mount) => {
            await invalidateGeneratedOperations(queryClient, ['adminListMounts']);
            const messages = resourceMutationMessages('mount', 'create', mount.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('mount', 'create').errorTitle),
    });
};

export const useUpdateAdminMount = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminUpdateMountMutation(),
        onSuccess: async (mount, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListMounts']),
                queryClient.invalidateQueries({ queryKey: adminMountDetailKey(variables.path.id) }),
            ]);
            const messages = resourceMutationMessages('mount', 'update', mount.attributes.name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('mount', 'update').errorTitle),
    });
};

export const useDeleteAdminMount = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDeleteMountMutation(),
        onSuccess: async (_data, variables) => {
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminMountDetailKey(variables.path.mount_id) });
            await invalidateGeneratedOperations(queryClient, ['adminListMounts']);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('mount', 'delete', name);
            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('mount', 'delete').errorTitle),
    });
};

export const useAttachAdminMountEggs = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminAttachEggsToMountMutation(),
        onSuccess: async (mount) => {
            await queryClient.cancelQueries({ queryKey: adminMountQueryKey(mount.attributes.id) });
            queryClient.setQueryData(adminMountQueryKey(mount.attributes.id), mount);
            await invalidateGeneratedOperations(queryClient, ['adminListMounts']);
            toast.success('Eggs attached', { description: 'The selected eggs have been attached to this mount.' });
        },
        onError: (error) => notifyHttpError(error, 'Unable to attach eggs'),
    });
};

export const useAttachAdminMountNodes = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminAttachNodesToMountMutation(),
        onSuccess: async (mount) => {
            await queryClient.cancelQueries({ queryKey: adminMountQueryKey(mount.attributes.id) });
            queryClient.setQueryData(adminMountQueryKey(mount.attributes.id), mount);
            await invalidateGeneratedOperations(queryClient, ['adminListMounts']);
            toast.success('Nodes attached', { description: 'The selected nodes have been attached to this mount.' });
        },
        onError: (error) => notifyHttpError(error, 'Unable to attach nodes'),
    });
};

export const useDetachAdminMountEgg = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDetachEggFromMountMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListMounts']),
                queryClient.invalidateQueries({ queryKey: adminMountDetailKey(variables.path.mount_id) }),
            ]);
            const name = variables.meta?.name;
            toast.success('Egg detached', {
                description: name
                    ? `${name} has been detached from this mount.`
                    : 'The egg has been detached from this mount.',
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to detach egg'),
    });
};

export const useDetachAdminMountNode = () => {
    const queryClient = useQueryClient();
    return useMutation({
        ...adminDetachNodeFromMountMutation(),
        onSuccess: async (_data, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListMounts']),
                queryClient.invalidateQueries({ queryKey: adminMountDetailKey(variables.path.mount_id) }),
            ]);
            const name = variables.meta?.name;
            toast.success('Node detached', {
                description: name
                    ? `${name} has been detached from this mount.`
                    : 'The node has been detached from this mount.',
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to detach node'),
    });
};
