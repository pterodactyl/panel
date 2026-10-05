import { useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { removeListItems, upsertListItem } from '@/api/queryData';
import {
    clientCreateServerSubuserMutation,
    clientDeleteServerSubuserMutation,
    clientListServerSubusersOptions,
    clientListServerSubusersQueryKey,
    clientUpdateServerSubuserMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    ClientCreateServerSubuserData,
    ClientDeleteServerSubuserData,
    ClientListServerSubusersData,
    ClientListServerSubusersResponse,
    ClientServerSubuserAttributes,
    ClientServerSubuserResource,
    ClientUpdateServerSubuserData,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type SubuserPermission = ClientServerSubuserAttributes['permissions'][number];
export type Subuser = ClientServerSubuserResource;

export interface CreateOrUpdateSubuserValues {
    email: string;
    permissions: SubuserPermission[];
}

const serverSubusersInput = (uuid: string): Options<ClientListServerSubusersData> => ({
    path: { server_uuid: uuid },
});

export const serverSubusersQueryOptions = (uuid: string) => clientListServerSubusersOptions(serverSubusersInput(uuid));

export const useServerSubusers = (uuid: string) =>
    useQuery({ ...serverSubusersQueryOptions(uuid), enabled: uuid.length > 0 });

export const createServerSubuserInput = (
    uuid: string,
    values: CreateOrUpdateSubuserValues
): Options<ClientCreateServerSubuserData> => ({
    path: { server_uuid: uuid },
    body: values,
});

export const updateServerSubuserInput = (
    uuid: string,
    subuser: Subuser,
    values: CreateOrUpdateSubuserValues
): Options<ClientUpdateServerSubuserData> => ({
    path: { server_uuid: uuid, user: subuser.attributes.uuid },
    body: { permissions: values.permissions },
});

export const deleteServerSubuserInput = (uuid: string, subuser: Subuser): Options<ClientDeleteServerSubuserData> => ({
    path: { server_uuid: uuid, user: subuser.attributes.uuid },
});

const upsertSubuser = async (queryClient: QueryClient, uuid: string, subuser: Subuser) => {
    await queryClient.cancelQueries({ queryKey: clientListServerSubusersQueryKey(serverSubusersInput(uuid)) });
    return queryClient.setQueryData<ClientListServerSubusersResponse>(
        clientListServerSubusersQueryKey(serverSubusersInput(uuid)),
        (current) => upsertListItem(current, subuser, (item) => item.attributes.uuid === subuser.attributes.uuid)
    );
};

const removeSubuser = async (queryClient: QueryClient, uuid: string, subuserUuid: string) => {
    await queryClient.cancelQueries({ queryKey: clientListServerSubusersQueryKey(serverSubusersInput(uuid)) });
    return queryClient.setQueryData<ClientListServerSubusersResponse>(
        clientListServerSubusersQueryKey(serverSubusersInput(uuid)),
        (current) => removeListItems(current, (subuser) => subuser.attributes.uuid === subuserUuid)
    );
};

export const useCreateSubuser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateServerSubuserMutation(),
        onSuccess: (subuser, { path }) => upsertSubuser(queryClient, path.server_uuid, subuser),
        onError: (error) => notifyHttpError(error, 'Unable to invite subuser'),
    });
};

export const useUpdateSubuser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateServerSubuserMutation(),
        onSuccess: (subuser, { path }) => upsertSubuser(queryClient, path.server_uuid, subuser),
        onError: (error) => notifyHttpError(error, 'Unable to update subuser'),
    });
};

export const useDeleteSubuser = (subuser: Subuser) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteServerSubuserMutation(),
        onSuccess: async (_data, { path }) => {
            await removeSubuser(queryClient, path.server_uuid, path.user);
            toast.success('Subuser removed', {
                description: `${subuser.attributes.email} no longer has access to this server.`,
            });
        },
        onError: (error) => notifyHttpError(error, 'Unable to remove subuser'),
    });
};
