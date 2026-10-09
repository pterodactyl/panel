import { queryOptions, useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';
import { listQuery, listSorts } from '@/api/queryParameters';

import { invalidateGeneratedOperations, resourceMutationMessages } from '@/api/mutationUtils';
import { fetchAllPages, MAX_PER_PAGE } from '@/api/pagination';
import {
    adminCreateTagMutation,
    adminDeleteTagMutation,
    adminListTagsQueryKey,
    adminListTagsOptions,
    adminSyncEggTagsMutation,
    adminUpdateTagMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListTags,
    type AdminCreateTagData,
    type AdminDeleteTagData,
    type AdminSyncEggTagsData,
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
    path: { id },
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
            const messages = resourceMutationMessages('tag', 'create', tag.attributes.name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('tag', 'create').errorTitle),
    });
};

export const useUpdateAdminTag = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateTagMutation(),
        onSuccess: async (tag) => {
            await invalidateGeneratedOperations(queryClient, assignedTagOperations);
            const messages = resourceMutationMessages('tag', 'update', tag.attributes.name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('tag', 'update').errorTitle),
    });
};

export const useDeleteAdminTag = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteTagMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateGeneratedOperations(queryClient, assignedTagOperations);
            const messages = resourceMutationMessages('tag', 'delete', variables.meta?.name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('tag', 'delete').errorTitle),
    });
};

export const useSyncAdminEggTags = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminSyncEggTagsMutation(),
        onSuccess: async () => {
            await invalidateGeneratedOperations(queryClient, ['adminGetEgg', 'adminListEggs', 'adminListTags']);
            toast.success('Egg tags updated', { description: 'The egg tag assignments have been saved.' });
        },
        onError: (error) => notifyHttpError(error, 'Unable to update egg tags'),
    });
};
