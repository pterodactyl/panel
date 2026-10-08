import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { type ListSort, type QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { allPagesQueryOptions, listItems, MAX_PER_PAGE } from '@/api/pagination';
import { withQueryOverrides, type ResourceQueryOverrides } from '@/api/resourceQuery';
import {
    invalidateGeneratedOperations,
    removeQueriesWhenUnobserved,
    resourceMutationMessages,
} from '@/api/mutationUtils';
import { currentUserQueryKey, setCurrentUserQueryData } from '@/api/account/queries';
import type { UserData } from '@/api/account/types';
import {
    adminCreateUserMutation,
    adminDeleteUserMutation,
    adminGetUserOptions,
    adminGetUserQueryKey,
    adminListUsersOptions,
    adminListUsersQueryKey,
    adminUpdateUserMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    adminListUsers,
    type AdminCreateUserData,
    type AdminDeleteUserData,
    type AdminGetUserData,
    type AdminGetUserResponse,
    type AdminListUsersData,
    type AdminServerResource,
    type AdminUpdateUserData,
    type AdminUserResource,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

import type { UserValues } from './types';
export type { UserValues } from './types';

export type UsersFilters = 'email' | 'uuid' | 'username' | 'external_id' | 'search';
export type UserListSort = ListSort<'email' | 'username' | 'created_at'>;
type UsersSorts = 'id' | 'uuid' | 'email' | 'username' | 'created_at';
export type AdminUsersQueryParams = QueryBuilderParams<UsersFilters, UsersSorts>;
export type AdminUser = AdminUserResource;
export type AdminUserServer = AdminServerResource;
export type AdminUserWithServers = AdminGetUserResponse;

const userListSortFields = ['email', 'username', 'created_at'] as const;

export const adminUserListSorts = (sort?: UserListSort): AdminUsersQueryParams['sorts'] =>
    listSorts(userListSortFields, sort);

export const adminUserListQueryParams = (page: number, filter: string, sort?: UserListSort): AdminUsersQueryParams => ({
    page,
    filters: { search: filter },
    sorts: adminUserListSorts(sort),
});

const adminUserInput = (id: number): Options<AdminGetUserData> => ({
    path: { user_id: id },
    query: { include: 'servers' },
});

export const adminUserDetailKey = (id: number) => adminGetUserQueryKey({ path: { user_id: id } });

const userValuesToBody = (values: UserValues): AdminCreateUserData['body'] => ({
    email: values.email,
    username: values.username,
    name_first: values.nameFirst,
    name_last: values.nameLast,
    password: values.password || undefined,
    root_admin: values.rootAdmin,
    preferences: { language: values.language },
});

export const createAdminUserInput = (values: UserValues): Options<AdminCreateUserData> => ({
    body: userValuesToBody(values),
});

export const updateAdminUserInput = (id: number, values: UserValues): Options<AdminUpdateUserData> => ({
    path: { id },
    body: userValuesToBody(values),
});

export const deleteAdminUserInput = (id: number, email?: string): Options<AdminDeleteUserData> => ({
    path: { user_id: id },
    meta: email ? { email } : undefined,
});

export const adminUsersQueryOptions = (params: AdminUsersQueryParams = {}) =>
    adminListUsersOptions({ query: listQuery(params) });

const allAdminUsersInput = { query: { sort: 'id', per_page: MAX_PER_PAGE } } satisfies Options<AdminListUsersData>;

export const allAdminUsersQueryOptions = () =>
    allPagesQueryOptions(adminListUsersQueryKey(allAdminUsersInput), (page, signal) =>
        adminListUsers({ query: { ...allAdminUsersInput.query, page }, signal }).then(({ data }) => data)
    );

export const adminUserWithServersQueryOptions = (id: number) => ({
    ...adminGetUserOptions(adminUserInput(id)),
    enabled: Number.isInteger(id) && id > 0,
});

type AdminUsersQueryOptions = Pick<ReturnType<typeof adminUsersQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

export const useAdminUsers = (params: AdminUsersQueryParams = {}, options?: AdminUsersQueryOptions) =>
    useQuery({ ...adminUsersQueryOptions(params), ...options });

export const useAllAdminUsers = () => useQuery({ ...allAdminUsersQueryOptions(), select: listItems });

export const useAdminUserWithServers = (id: number, options?: ResourceQueryOverrides) =>
    useQuery(withQueryOverrides(adminUserWithServersQueryOptions(id), options));

const currentUserFields = ({ attributes }: AdminUser): Partial<UserData> => ({
    username: attributes.username,
    email: attributes.email,
    language: attributes.language,
    rootAdmin: attributes.root_admin,
    useTotp: attributes['2fa'],
});

export const useCreateAdminUser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateUserMutation(),
        onSuccess: async (user) => {
            await invalidateGeneratedOperations(queryClient, ['adminListUsers']);
            const messages = resourceMutationMessages('user', 'create', user.attributes.email);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('user', 'create').errorTitle),
    });
};

export const useUpdateAdminUser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminUpdateUserMutation(),
        onSuccess: async (user, variables) => {
            if (queryClient.getQueryData<UserData>(currentUserQueryKey)?.uuid === user.attributes.uuid) {
                setCurrentUserQueryData(queryClient, currentUserFields(user));
            }

            await Promise.all([
                invalidateGeneratedOperations(queryClient, ['adminListUsers']),
                queryClient.invalidateQueries({ queryKey: adminUserDetailKey(variables.path.id) }),
            ]);
            const messages = resourceMutationMessages('user', 'update', user.attributes.email);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('user', 'update').errorTitle),
    });
};

export const useDeleteAdminUser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteUserMutation(),
        onSuccess: async (_data, variables) => {
            removeQueriesWhenUnobserved(queryClient, { queryKey: adminUserDetailKey(variables.path.user_id) });
            await invalidateGeneratedOperations(queryClient, ['adminListUsers']);
            const name = variables.meta?.email;
            const messages = resourceMutationMessages('user', 'delete', name);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) => notifyHttpError(error, resourceMutationMessages('user', 'delete').errorTitle),
    });
};
