import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { removeListItems, upsertListItem } from '@/api/queryData';
import {
    clientCreateSshKeyMutation,
    clientDeleteSshKeyMutation,
    clientListSshKeysOptions,
    clientListSshKeysQueryKey,
} from '@/api/generated/@tanstack/react-query.gen';
import {
    type ClientDeleteSshKeyData,
    type ClientListSshKeysResponse,
    type ClientSshKeyResource,
    type Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type SSHKey = ClientSshKeyResource;

export const accountSshKeysQueryOptions = () => ({
    ...clientListSshKeysOptions(),
});

export const useSSHKeys = () => useQuery(accountSshKeysQueryOptions());

export const useCreateSSHKey = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateSshKeyMutation(),
        onSuccess: async (key) => {
            await queryClient.cancelQueries({ queryKey: clientListSshKeysQueryKey() });
            queryClient.setQueryData<ClientListSshKeysResponse>(clientListSshKeysQueryKey(), (current) =>
                upsertListItem(current, key, (item) => item.attributes.fingerprint === key.attributes.fingerprint)
            );
            toast.success('SSH key created');
        },
        onError: (error) => notifyHttpError(error, 'Unable to create SSH key'),
    });
};

export const useDeleteSSHKey = () => {
    const queryClient = useQueryClient();
    const queryKey = clientListSshKeysQueryKey();

    return useMutation({
        ...clientDeleteSshKeyMutation(),
        onMutate: async (variables: Options<ClientDeleteSshKeyData>) => {
            await queryClient.cancelQueries({ queryKey });
            const previous = queryClient.getQueryData<ClientListSshKeysResponse>(queryKey);

            queryClient.setQueryData<ClientListSshKeysResponse>(queryKey, (current) =>
                removeListItems(current, (key: SSHKey) => key.attributes.fingerprint === variables.body.fingerprint)
            );

            return {
                removed: previous?.data.find((item) => item.attributes.fingerprint === variables.body.fingerprint),
            };
        },
        onSuccess: () => {
            toast.success('SSH key deleted');
        },
        onSettled: () => {
            if (queryClient.isMutating({ mutationKey: clientDeleteSshKeyMutation().mutationKey }) === 1) {
                return queryClient.invalidateQueries({ queryKey });
            }
            return Promise.resolve();
        },
        onError: (error, _variables, context) => {
            if (context?.removed) {
                const removed = context.removed;
                queryClient.setQueryData<ClientListSshKeysResponse>(queryKey, (current) =>
                    upsertListItem(
                        current,
                        removed,
                        (item) => item.attributes.fingerprint === removed.attributes.fingerprint
                    )
                );
            }
            notifyHttpError(error, 'Unable to delete SSH key');
        },
    });
};
