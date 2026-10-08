import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import type { ListSort, QueryBuilderParams } from '@/api/queryParameters';

import { listQuery, listSorts } from '@/api/queryParameters';
import { invalidateGeneratedOperations, resourceMutationMessages } from '@/api/mutationUtils';
import {
    adminCreateApiKeyMutation,
    adminDeleteApiKeyMutation,
    adminListApiKeysOptions,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    type AdminApiKeyResource,
    type AdminCreateApiKeyData,
    type AdminDeleteApiKeyData,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

type ApiKeysFilters = 'memo' | 'identifier';
export type ApiKeyListSort = ListSort<'memo' | 'created_at'>;
type ApiKeysSorts = 'id' | 'memo' | 'created_at';

export type AdminApiKeysQueryParams = QueryBuilderParams<ApiKeysFilters, ApiKeysSorts>;

// Mirrors app/Services/Acl/Api/AdminAcl.php; stored per-key as r_<resource>.
export const API_KEY_RESOURCES = [
    'servers',
    'nodes',
    'allocations',
    'users',
    'locations',
    'eggs',
    'database_hosts',
    'server_databases',
] as const;

export type ApiKeyResource = (typeof API_KEY_RESOURCES)[number];

export interface ApiKeyValues {
    memo: string;
    permissions: Record<ApiKeyResource, number>;
}

export type AdminApiKey = AdminApiKeyResource;

export type ApiKeyPermissionField = `r_${ApiKeyResource}`;

export const createAdminApiKeyInput = (values: ApiKeyValues): Options<AdminCreateApiKeyData> => ({
    body: {
        memo: values.memo,
        r_servers: values.permissions.servers,
        r_nodes: values.permissions.nodes,
        r_allocations: values.permissions.allocations,
        r_users: values.permissions.users,
        r_locations: values.permissions.locations,
        r_eggs: values.permissions.eggs,
        r_database_hosts: values.permissions.database_hosts,
        r_server_databases: values.permissions.server_databases,
    },
});

export const deleteAdminApiKeyInput = (identifier: string): Options<AdminDeleteApiKeyData> => ({
    path: { identifier },
});

const permissionField = (resource: ApiKeyResource): ApiKeyPermissionField => `r_${resource}`;

export const permissionValue = (apiKey: AdminApiKey, resource: ApiKeyResource): number =>
    apiKey.attributes[permissionField(resource)] ?? 0;

const apiKeyListSortFields = ['memo', 'created_at'] as const;

export const adminApiKeyListSorts = (sort?: ApiKeyListSort): AdminApiKeysQueryParams['sorts'] =>
    listSorts(apiKeyListSortFields, sort);

export const adminApiKeyListQueryParams = (
    page: number,
    filter: string,
    sort?: ApiKeyListSort
): AdminApiKeysQueryParams => ({
    page,
    filters: { memo: filter },
    sorts: adminApiKeyListSorts(sort),
});

export const adminApiKeysQueryOptions = (params: AdminApiKeysQueryParams = {}) =>
    adminListApiKeysOptions({ query: listQuery(params) });

type AdminApiKeysQueryOptions = Pick<ReturnType<typeof adminApiKeysQueryOptions>, 'placeholderData'> & {
    enabled?: boolean;
};

export const useAdminApiKeys = (params: AdminApiKeysQueryParams = {}, options?: AdminApiKeysQueryOptions) =>
    useQuery({ ...adminApiKeysQueryOptions(params), ...options });

export const useCreateAdminApiKey = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminCreateApiKeyMutation(),
        onSuccess: async (apiKey) => {
            await invalidateGeneratedOperations(queryClient, ['adminListApiKeys']);
            const messages = resourceMutationMessages('application API key', 'create', apiKey.attributes.identifier);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) =>
            notifyHttpError(error, resourceMutationMessages('application API key', 'create').errorTitle),
    });
};

export const useDeleteAdminApiKey = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...adminDeleteApiKeyMutation(),
        onSuccess: async (_data, variables) => {
            await invalidateGeneratedOperations(queryClient, ['adminListApiKeys']);
            const messages = resourceMutationMessages('application API key', 'delete', variables.path.identifier);

            toast.success(messages.success.title, { description: messages.success.description });
        },
        onError: (error) =>
            notifyHttpError(error, resourceMutationMessages('application API key', 'delete').errorTitle),
    });
};
