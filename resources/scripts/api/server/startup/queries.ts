import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { useUpdateCurrentServer } from '@/api/server/queries';
import { updateListItems } from '@/api/queryData';
import { retryUnlessClientError } from '@/api/queryRetry';
import {
    clientGetStartupConfigurationOptions,
    clientGetStartupConfigurationQueryKey,
    clientSetServerDockerImageMutation,
    clientUpdateStartupVariableMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    ClientGetStartupConfigurationData,
    ClientGetStartupConfigurationResponse,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type ServerStartupVariable = ClientGetStartupConfigurationResponse['data'][number];

const serverStartupInput = (uuid: string): Options<ClientGetStartupConfigurationData> => ({
    path: { server_uuid: uuid },
});

const retryStartupRead = retryUnlessClientError(3);

export const serverStartupQueryOptions = (
    uuid: string
): ReturnType<typeof clientGetStartupConfigurationOptions> & { retry: typeof retryStartupRead } => ({
    ...clientGetStartupConfigurationOptions(serverStartupInput(uuid)),
    retry: retryStartupRead,
});

export const useServerStartup = (uuid: string, config?: { enabled?: boolean }) =>
    useQuery({ ...serverStartupQueryOptions(uuid), enabled: uuid.length > 0 && config?.enabled !== false });

export const useSetSelectedDockerImage = (uuid: string) => {
    const updateCurrentServer = useUpdateCurrentServer();

    return useMutation({
        ...clientSetServerDockerImageMutation(),
        scope: { id: `server-startup:${uuid}` },
        onSuccess: (_data, variables) => {
            updateCurrentServer((server) => ({
                ...server,
                attributes: { ...server.attributes, docker_image: variables.body.docker_image },
            }));
            toast.success('Docker image updated');
        },
        onError: (error) => notifyHttpError(error, 'Unable to update Docker image'),
    });
};

export const useUpdateStartupVariable = (uuid: string) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateStartupVariableMutation(),
        scope: { id: `server-startup:${uuid}` },
        onSuccess: async (resource, variables) => {
            await queryClient.cancelQueries({
                queryKey: clientGetStartupConfigurationQueryKey(serverStartupInput(variables.path.server_uuid)),
            });
            queryClient.setQueryData<ClientGetStartupConfigurationResponse>(
                clientGetStartupConfigurationQueryKey(serverStartupInput(variables.path.server_uuid)),
                (data) => {
                    const updated = updateListItems(
                        data,
                        (item) => item.attributes.env_variable === resource.attributes.env_variable,
                        () => resource
                    );

                    if (!updated?.meta) {
                        return updated;
                    }

                    return {
                        ...updated,
                        meta: {
                            ...updated.meta,
                            startup_command: resource.meta?.startup_command ?? updated.meta.startup_command,
                        },
                    };
                }
            );
        },
        onError: (error) => notifyHttpError(error, 'Unable to update startup variable'),
    });
};
