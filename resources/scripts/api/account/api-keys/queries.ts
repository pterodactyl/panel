import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { removeListItems, upsertListItem } from '@/api/queryData';
import {
    clientCreateAccountApiKeyMutation,
    clientDeleteAccountApiKeyMutation,
    clientListAccountApiKeysOptions,
    clientListAccountApiKeysQueryKey,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    type ClientApiKeyResource,
    type ClientDeleteAccountApiKeyData,
    type ClientListAccountApiKeysResponse,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type ApiKey = ClientApiKeyResource;

export const accountApiKeysQueryOptions = () => clientListAccountApiKeysOptions();

export const useAccountApiKeys = () => useQuery(accountApiKeysQueryOptions());

export const useCreateAccountApiKey = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateAccountApiKeyMutation(),
        onSuccess: async (created) => {
            await queryClient.cancelQueries({ queryKey: clientListAccountApiKeysQueryKey() });
            queryClient.setQueryData<ClientListAccountApiKeysResponse>(clientListAccountApiKeysQueryKey(), (current) =>
                upsertListItem(current, created, (key) => key.attributes.identifier === created.attributes.identifier)
            );
            toast.success('API key created');
        },
        onError: (error) => notifyHttpError(error, 'Unable to create API key'),
    });
};

export const useDeleteAccountApiKey = () => {
    const queryClient = useQueryClient();
    const queryKey = clientListAccountApiKeysQueryKey();

    return useMutation({
        ...clientDeleteAccountApiKeyMutation(),
        onMutate: async (variables: Options<ClientDeleteAccountApiKeyData>) => {
            await queryClient.cancelQueries({ queryKey });
            const previous = queryClient.getQueryData<ClientListAccountApiKeysResponse>(queryKey);

            queryClient.setQueryData<ClientListAccountApiKeysResponse>(queryKey, (current) =>
                removeListItems(current, (key: ApiKey) => key.attributes.identifier === variables.path.identifier)
            );

            return { removed: previous?.data.find((item) => item.attributes.identifier === variables.path.identifier) };
        },
        onSuccess: () => {
            toast.success('API key deleted');
        },
        onSettled: () => {
            if (queryClient.isMutating({ mutationKey: clientDeleteAccountApiKeyMutation().mutationKey }) === 1) {
                return queryClient.invalidateQueries({ queryKey });
            }

            return Promise.resolve();
        },
        onError: (error, _variables, context) => {
            if (context?.removed) {
                const removed = context.removed;

                queryClient.setQueryData<ClientListAccountApiKeysResponse>(queryKey, (current) =>
                    upsertListItem(
                        current,
                        removed,
                        (item) => item.attributes.identifier === removed.attributes.identifier
                    )
                );
            }

            notifyHttpError(error, 'Unable to delete API key');
        },
    });
};
