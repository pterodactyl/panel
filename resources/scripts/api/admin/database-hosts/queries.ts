import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { allPagesQueryOptions, listItems, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import {
    adminCreateDatabaseHostMutation,
    adminDeleteDatabaseHostMutation,
    adminGetDatabaseHostOptions,
    adminGetDatabaseHostQueryKey,
    adminListDatabaseHostDatabasesOptions,
    adminListDatabaseHostDatabasesQueryKey,
    adminListDatabaseHostsOptions,
    adminListDatabaseHostsQueryKey,
    adminUpdateDatabaseHostMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListDatabaseHosts,
    type AdminCreateDatabaseHostData,
    type AdminDatabaseHostResource,
    type AdminDeleteDatabaseHostData,
    type AdminGetDatabaseHostData,
    type AdminListDatabaseHostDatabasesData,
    type AdminListDatabaseHostsData,
    type AdminUpdateDatabaseHostData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type AdminDatabaseHost = AdminDatabaseHostResource;

export type DatabaseHostsFilters = 'name' | 'host';
type DatabaseHostsSorts = 'id' | 'name' | 'created_at';
export type AdminDatabaseHostsQueryParams = QueryBuilderParams<DatabaseHostsFilters, DatabaseHostsSorts>;
export type AdminDatabaseHostListSort = ListSort<'name' | 'created_at'>;
export type DatabaseHostBody = AdminCreateDatabaseHostData['body'];

const databaseHostListInclude = 'node';
const databaseHostDetailInclude = 'node';

export const createAdminDatabaseHostInput = (body: DatabaseHostBody): Options<AdminCreateDatabaseHostData> => ({
    body,
});

export const updateAdminDatabaseHostInput = (
    id: number,
    body: AdminUpdateDatabaseHostData['body']
): Options<AdminUpdateDatabaseHostData> => ({
    path: { databaseHost_id: String(id) },
    body,
});

export const deleteAdminDatabaseHostInput = (id: number, name?: string): Options<AdminDeleteDatabaseHostData> => ({
    path: { databaseHost_id: String(id) },
    meta: name ? { name } : undefined,
});

const databaseHostListSortFields = ['name', 'created_at'] as const;

export const adminDatabaseHostListSorts = (sort?: AdminDatabaseHostListSort): AdminDatabaseHostsQueryParams['sorts'] =>
    listSorts(databaseHostListSortFields, sort);

export const adminDatabaseHostListQueryParams = (
    page: number,
    filter: string,
    sort?: AdminDatabaseHostListSort
): AdminDatabaseHostsQueryParams => ({
    page,
    filters: { name: filter },
    sorts: adminDatabaseHostListSorts(sort),
});

const adminDatabaseHostInput = (id: number): Options<AdminGetDatabaseHostData> => ({
    path: { databaseHost_id: String(id) },
    query: { include: databaseHostDetailInclude },
});

export const adminDatabaseHostDetailKey = (id: string | number) =>
    adminGetDatabaseHostQueryKey({ path: { databaseHost_id: String(id) } });

const adminDatabaseHostDatabasesKey = (id: string | number) =>
    adminListDatabaseHostDatabasesQueryKey({ path: { databaseHost_id: String(id) } });

const adminDatabaseHostDatabasesInput = (id: number, page = 1): Options<AdminListDatabaseHostDatabasesData> => ({
    path: { databaseHost_id: String(id) },
    query: { page },
});

export const adminDatabaseHostsQueryOptions = (params: AdminDatabaseHostsQueryParams = {}) =>
    adminListDatabaseHostsOptions({ query: { ...listQuery(params), include: databaseHostListInclude } });

const allAdminDatabaseHostsInput = {
    query: { sort: 'id', per_page: MAX_PER_PAGE, include: databaseHostListInclude },
} satisfies Options<AdminListDatabaseHostsData>;

export const allAdminDatabaseHostsQueryOptions = () =>
    allPagesQueryOptions(adminListDatabaseHostsQueryKey(allAdminDatabaseHostsInput), (page, signal) =>
        adminListDatabaseHosts({ query: { ...allAdminDatabaseHostsInput.query, page }, signal }).then(
            ({ data }) => data
        )
    );

export const adminDatabaseHostQueryOptions = (id: number) => ({
    ...adminGetDatabaseHostOptions(adminDatabaseHostInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

export const adminDatabaseHostDatabasesQueryOptions = (id: number, page = 1) => ({
    ...adminListDatabaseHostDatabasesOptions(adminDatabaseHostDatabasesInput(id, page)),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminDatabaseHostsQueryOptions = Pick<ReturnType<typeof adminDatabaseHostsQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

export const useAdminDatabaseHosts = (
    params: AdminDatabaseHostsQueryParams = {},
    options?: AdminDatabaseHostsQueryOptions
) => useQuery({ ...adminDatabaseHostsQueryOptions(params), ...options });

export const useAllAdminDatabaseHosts = (options?: ResourceQueryOverrides) =>
    useQuery({ ...allAdminDatabaseHostsQueryOptions(), enabled: options?.enabled, select: listItems });

export const useAdminDatabaseHost = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminDatabaseHostQueryOptions(id), options));

export const useAdminDatabaseHostDatabases = (id: number, page = 1, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminDatabaseHostDatabasesQueryOptions(id, page), options));

export const useCreateAdminDatabaseHost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateDatabaseHostMutation(),
        onSuccess: async (host) => {
            await invalidateGeneratedOperations(queryClient, ['adminListDatabaseHosts']);
            const messages = resourceMutationMessages('database host', 'create', host.attributes.name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('database host', 'create').errorTitle),
    });
};

export const useUpdateAdminDatabaseHost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateDatabaseHostMutation(),
        onSuccess: async (host, variables) => {
            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListDatabaseHosts']),
                queryClient.invalidateQueries({
                    queryKey: adminDatabaseHostDetailKey(variables.path.databaseHost_id),
                }),
            ]);
            const messages = resourceMutationMessages('database host', 'update', host.attributes.name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('database host', 'update').errorTitle),
    });
};

export const useDeleteAdminDatabaseHost = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteDatabaseHostMutation(),
        onSuccess: async (_data, variables) => {
            const id = variables.path.databaseHost_id;

            removeQueriesWhenUnobserved(queryClient, { queryKey: adminDatabaseHostDetailKey(id) });
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminDatabaseHostDatabasesKey(id) });
            await invalidateGeneratedOperations(queryClient, ['adminListDatabaseHosts']);
            const name = variables.meta?.name;
            const messages = resourceMutationMessages('database host', 'delete', name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('database host', 'delete').errorTitle),
    });
};
