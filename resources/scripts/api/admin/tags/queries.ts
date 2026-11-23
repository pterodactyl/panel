import { queryOptions, useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import i18next from 'i18next';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';
import { listQuery, listSorts } from '@/api/queryParameters';

import { invalidateGeneratedOperations } from '@/api/mutationUtils';
import { fetchAllPages, MAX_PER_PAGE } from '@/api/pagination';
import { adminNodeDetailKey } from '@/api/admin/nodes/invalidation';
import {
    adminCreateTagMutation,
    adminDeleteTagMutation,
    adminListTagsQueryKey,
    adminListTagsOptions,
    adminSyncEggTagsMutation,
    adminSyncNodeDeploymentTagsMutation,
    adminSyncNodeGameTagsMutation,
    adminUpdateTagMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListTags,
    type AdminCreateTagData,
    type AdminDeleteTagData,
    type AdminSyncEggTagsData,
    type AdminSyncNodeDeploymentTagsData,
    type AdminSyncNodeGameTagsData,
    type AdminTagResource,
    type AdminUpdateTagData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

type TagFilters = 'name' | 'slug';
type TagSorts = 'id' | 'name' | 'slug';

export type AdminTag = AdminTagResource;
export type AdminTagValues = AdminCreateTagData['body'];
export type AdminTagListSort = ListSort<'name' | 'slug'>;
export type AdminTagsQueryParams = QueryBuilderParams<TagFilters, TagSorts>;

export const createAdminTagInput = (values: AdminTagValues): Options<AdminCreateTagData> => ({ body: values });

export const updateAdminTagInput = (id: number, values: AdminTagValues): Options<AdminUpdateTagData> => ({
    path: { tag_id: id },
    body: values,
});

export const deleteAdminTagInput = (id: number, name?: string): Options<AdminDeleteTagData> => ({
    path: { tag_id: id },
    meta: name ? { name } : undefined,
});

export const syncAdminEggTagsInput = (eggId: number, tags: string[]): Options<AdminSyncEggTagsData> => ({
    path: { egg_id: eggId },
    body: { tags },
});

export const syncAdminNodeGameTagsInput = (nodeId: number, tags: string[]): Options<AdminSyncNodeGameTagsData> => ({
    path: { node_id: nodeId },
    body: { tags },
});

export const syncAdminNodeDeploymentTagsInput = (
    nodeId: number,
    tags: string[]
): Options<AdminSyncNodeDeploymentTagsData> => ({
    path: { node_id: nodeId },
    body: { tags },
});

const tagListSortFields = ['name', 'slug'] as const;

export const adminTagListSorts = (sort?: AdminTagListSort): AdminTagsQueryParams['sorts'] =>
    listSorts(tagListSortFields, sort);

export const adminTagListQueryParams = (
    page: number,
    filter: string,
    sort?: AdminTagListSort
): AdminTagsQueryParams => ({
    page,
    filters: { name: filter },
    sorts: adminTagListSorts(sort),
});

export const adminTagsQueryOptions = (params: AdminTagsQueryParams = {}) =>
    adminListTagsOptions({ query: listQuery(params) });

type AdminTagsQueryOptions = Pick<ReturnType<typeof adminTagsQueryOptions>, 'placeholderData'> & { enabled?: boolean };

export const useAdminTags = (params: AdminTagsQueryParams = {}, options?: AdminTagsQueryOptions) =>
    useQuery({ ...adminTagsQueryOptions(params), ...options });

const allAdminTagsInput = { query: { sort: 'name,id', per_page: MAX_PER_PAGE } } as const;

export const allAdminTagsQueryOptions = () =>
    queryOptions({
        queryKey: [...adminListTagsQueryKey(allAdminTagsInput), 'all'],
        queryFn: async ({ signal }): Promise<AdminTag[]> => {
            const tags = await fetchAllPages(
                async (page) => (await adminListTags({ query: { ...allAdminTagsInput.query, page }, signal })).data,
                signal
            );

            return tags.data;
        },
    });

export const useAllAdminTags = () => useQuery(allAdminTagsQueryOptions());

const tagListOperations = ['adminListTags'];
const assignedTagOperations = [...tagListOperations, 'adminGetEgg', 'adminListEggs', 'adminGetNode', 'adminListNodes'];

export const useCreateAdminTag = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateTagMutation(),
        onSuccess: async (tag) => {
            await invalidateGeneratedOperations(queryClient, tagListOperations);
            toast.success(i18next.t('Tag created'), {
                description: i18next.t('{{name}} has been created.', { name: tag.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to create tag')),
    });
};

export const useUpdateAdminTag = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateTagMutation(),
        onSuccess: async (tag) => {
            await invalidateGeneratedOperations(queryClient, assignedTagOperations);
            toast.success(i18next.t('Tag updated'), {
                description: i18next.t('{{name}} has been updated.', { name: tag.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update tag')),
    });
};

export const useDeleteAdminTag = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteTagMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateGeneratedOperations(queryClient, assignedTagOperations);
            const name = variables.meta?.name;

            toast.success(i18next.t('Tag deleted'), {
                description: name
                    ? i18next.t('{{name}} has been deleted.', { name })
                    : i18next.t('The tag has been deleted.'),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to delete tag')),
    });
};

export const useSyncAdminEggTags = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminSyncEggTagsMutation(),
        onSuccess: async () => {
            await invalidateGeneratedOperations(queryClient, ['adminGetEgg', 'adminListEggs', 'adminListTags']);
            toast.success(i18next.t('Egg tags updated'), {
                description: i18next.t('The egg tag assignments have been saved.'),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update egg tags')),
    });
};

const onNodeTagsSynced = async (queryClient: QueryClient, nodeId: number) => {
    await Promise.all([
        queryClient.invalidateQueries({ queryKey: adminNodeDetailKey(nodeId) }),
        invalidateGeneratedOperations(queryClient, ['adminListNodes', 'adminListTags']),
    ]);
    // Games and reservations save together, so both syncs update one toast.
    toast.success(i18next.t('Node tags updated'), {
        id: `admin-node-tags-${nodeId}`,
        description: i18next.t('The node tag assignments have been saved.'),
    });
};

export const useSyncAdminNodeGameTags = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminSyncNodeGameTagsMutation(),
        onSuccess: (_tags, variables) => onNodeTagsSynced(queryClient, variables.path.node_id),
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update node tags')),
    });
};

export const useSyncAdminNodeDeploymentTags = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminSyncNodeDeploymentTagsMutation(),
        onSuccess: (_tags, variables) => onNodeTagsSynced(queryClient, variables.path.node_id),
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update node tags')),
    });
};
