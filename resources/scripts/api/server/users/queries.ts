import { useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';

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

/** `subuser` is the subuser's user uuid. */
export const updateServerSubuserInput = (
    uuid: string,
    subuser: string,
    values: CreateOrUpdateSubuserValues
): Options<ClientUpdateServerSubuserData> => ({
    path: { server_uuid: uuid, user: subuser },
    body: { permissions: values.permissions },
});

/** `subuser` is the subuser's user uuid. */
export const deleteServerSubuserInput = (uuid: string, subuser: string): Options<ClientDeleteServerSubuserData> => ({
    path: { server_uuid: uuid, user: subuser },
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

/** Reports failures; the caller announces success, since it knows the subuser's email. */
export const useDeleteSubuser = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteServerSubuserMutation(),
        onSuccess: (_data, { path }) => removeSubuser(queryClient, path.server_uuid, path.user),
        onError: (error) => notifyHttpError(error, 'Unable to remove subuser'),
    });
};
