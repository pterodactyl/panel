import { useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import i18next from 'i18next';
import { toast } from 'sonner';
import { useUpdateCurrentServer } from '@/api/server/queries';

import { updateListItems } from '@/api/queryData';
import { notifyHttpError } from '@/plugins/notifications';
import {
    clientCreateServerBackupMutation,
    clientDeleteServerBackupMutation,
    clientListServerBackupsOptions,
    clientListServerBackupsQueryKey,
    clientRestoreServerBackupMutation,
    clientToggleBackupLockMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import { clientGetBackupDownloadUrl } from '@/api/generated';
import type {
    ClientCreateServerBackupData,
    ClientDeleteServerBackupData,
    ClientGetBackupDownloadUrlData,
    ClientListServerBackupsData,
    ClientListServerBackupsResponse,
    ClientBackupResource,
    ClientRestoreServerBackupData,
    ClientToggleBackupLockData,
    Options,
} from '@/api/generated';

export type ServerBackup = ClientBackupResource;

const serverBackupsInput = (uuid: string, page: number): Options<ClientListServerBackupsData> => ({
    path: { server_uuid: uuid },
    query: { page },
});

export const createServerBackupInput = (
    uuid: string,
    values: { name?: string; ignored?: string; isLocked: boolean }
): Options<ClientCreateServerBackupData> => ({
    path: { server_uuid: uuid },
    body: {
        name: values.name,
        ignored: values.ignored,
        is_locked: values.isLocked,
    },
});

export const deleteServerBackupInput = (uuid: string, backup: ServerBackup): Options<ClientDeleteServerBackupData> => ({
    path: { server_uuid: uuid, backup_uuid: backup.attributes.uuid },
});

export const restoreServerBackupInput = (
    uuid: string,
    backup: ServerBackup,
    truncate: boolean
): Options<ClientRestoreServerBackupData> => ({
    path: { server_uuid: uuid, backup_uuid: backup.attributes.uuid },
    body: { truncate },
});

export const toggleServerBackupLockInput = (
    uuid: string,
    backup: ServerBackup
): Options<ClientToggleBackupLockData> => ({
    path: { server_uuid: uuid, backup_uuid: backup.attributes.uuid },
});

export const backupDownloadUrlInput = (
    uuid: string,
    backup: ServerBackup
): Options<ClientGetBackupDownloadUrlData> => ({
    path: { server_uuid: uuid, backup_uuid: backup.attributes.uuid },
});

export const serverBackupsQueryOptions = (uuid: string, page = 1) =>
    clientListServerBackupsOptions(serverBackupsInput(uuid, page));

export const useServerBackups = (uuid: string, page: number) =>
    useQuery({ ...serverBackupsQueryOptions(uuid, page), enabled: uuid.length > 0 });

export const updateServerBackup = (
    queryClient: QueryClient,
    uuid: string,
    page: number,
    backupId: string,
    updater: (backup: ServerBackup) => ServerBackup
) => {
    void queryClient.cancelQueries({ queryKey: clientListServerBackupsQueryKey(serverBackupsInput(uuid, page)) });
    queryClient.setQueryData<ClientListServerBackupsResponse>(
        clientListServerBackupsQueryKey(serverBackupsInput(uuid, page)),
        (current) => updateListItems(current, (backup) => backup.attributes.uuid === backupId, updater)
    );
};

export const useCreateServerBackup = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateServerBackupMutation(),
        onSuccess: async (backup, { path }) => {
            await queryClient.invalidateQueries({
                queryKey: clientListServerBackupsQueryKey({ path: { server_uuid: path.server_uuid } }),
            });
            toast.success(i18next.t('Backup started'), {
                description: i18next.t('{{name}} is being created.', { name: backup.attributes.name }),
            });
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to create backup')),
    });
};

/** Reports failures; the caller announces success, since it knows the backup's name. */
export const useDeleteServerBackup = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteServerBackupMutation(),
        onSuccess: (_data, { path }) =>
            queryClient.invalidateQueries({
                queryKey: clientListServerBackupsQueryKey({ path: { server_uuid: path.server_uuid } }),
            }),
        onError: (error) => notifyHttpError(error, i18next.t('Unable to delete backup')),
    });
};

/** Reports failures; the caller announces success, since it knows the backup's name. */
export const useRestoreServerBackup = () => {
    const updateCurrentServer = useUpdateCurrentServer();

    return useMutation({
        ...clientRestoreServerBackupMutation(),
        onSuccess: () => {
            updateCurrentServer((server) => ({
                ...server,
                attributes: { ...server.attributes, status: 'restoring_backup' },
            }));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to restore backup')),
    });
};

export const useToggleServerBackupLock = (page: number) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientToggleBackupLockMutation(),
        onSuccess: (updated, { path }) => {
            updateServerBackup(queryClient, path.server_uuid, page, path.backup_uuid, () => updated);
            toast.success(updated.attributes.is_locked ? i18next.t('Backup locked') : i18next.t('Backup unlocked'));
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to update backup lock')),
    });
};

export const useBackupDownloadUrl = () =>
    useMutation<string, Error, Options<ClientGetBackupDownloadUrlData>, unknown>({
        mutationFn: async (input) => {
            const { data } = await clientGetBackupDownloadUrl(input);

            if (!data) {
                throw new Error('Backup download URL response did not include data.');
            }

            return data.attributes.url;
        },
        onError: (error) => notifyHttpError(error, i18next.t('Unable to create backup download')),
    });
