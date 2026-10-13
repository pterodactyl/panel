import { useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { toast } from 'sonner';
import {
    backupDownloadUrlInput,
    createServerBackupInput,
    deleteServerBackupInput,
    restoreServerBackupInput,
    toggleServerBackupLockInput,
    updateServerBackup,
    useBackupDownloadUrl,
    useCreateServerBackup,
    useDeleteServerBackup,
    useRestoreServerBackup,
    useServerBackups,
    useToggleServerBackupLock,
    type ServerBackup,
} from '@/api/server/backups/queries';
import { SocketEvent } from '@/components/server/events';
import { usePermissions } from '@/plugins/usePermissions';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { getPageSearch, usePageSearch } from '@/router/pageSearch';
import { pageStatus, pageStatusFixtures, type PageStatusModel } from '../pageStatus';
import { implementViews } from '../registry';
import { completeBackup, parseBackupCompletedPayload } from './backupCompletion';
import { attempt } from './attempt';
import { useLoadedServer } from './loadedServer';

export interface BackupSummary {
    readonly uuid: string;
    readonly name: string;
    /** The archive's checksum, prefixed with its algorithm. */
    readonly checksum: string | null;
    readonly bytes: number;
    readonly createdAt: string;
    /** Null while the backup is still being made. */
    readonly completedAt: string | null;
    readonly successful: boolean;
    /** Locked backups cannot be deleted until they are unlocked. */
    readonly locked: boolean;
}
export interface BackupFormValues {
    readonly name: string;
    /** Files and folders to leave out, one pattern a line; the server's .pteroignore when empty. */
    readonly ignored: string;
    readonly isLocked: boolean;
}

export interface BackupListModel {
    /** The current page of backups, newest first; one completes live as the node reports it. */
    readonly backups: readonly BackupSummary[];
    readonly pagination: {
        readonly page: number;
        readonly perPage: number;
        /** Backups across every page. */
        readonly total: number;
        /** Backups on this page. */
        readonly count: number;
    };
    /** The backups the server may keep; none can be created at 0. */
    readonly limit: number;
    /** The backups the server has, failed ones aside. */
    readonly count: number;
    readonly permissions: {
        readonly create: boolean;
        readonly download: boolean;
        readonly restore: boolean;
        readonly delete: boolean;
    };
    readonly pending: {
        readonly download: boolean;
        readonly remove: boolean;
        readonly restore: boolean;
        readonly lock: boolean;
    };
    readonly validateName: (name: string) => string | undefined;
    /** Those resolving a boolean resolve whether they succeeded; a failure has already been reported. */
    readonly actions: {
        readonly setPage: (page: number) => void;
        readonly create: (values: BackupFormValues) => Promise<boolean>;
        /** Sends the browser to a signed download of the archive. */
        readonly download: (uuid: string) => Promise<void>;
        readonly remove: (uuid: string) => Promise<boolean>;
        /** Stops the server and restores the backup, deleting every file first when `truncate` is set. */
        readonly restore: (uuid: string, truncate: boolean) => Promise<boolean>;
        readonly toggleLock: (uuid: string) => Promise<boolean>;
    };
}

declare module '../registry' {
    interface ViewModels {
        'page.server.backups': PageStatusModel;
        'server.backups': BackupListModel;
    }
}

const backupSummary = ({ attributes }: ServerBackup): BackupSummary => ({
    uuid: attributes.uuid,
    name: attributes.name,
    checksum: attributes.checksum,
    bytes: attributes.bytes,
    createdAt: attributes.created_at,
    completedAt: attributes.completed_at,
    successful: attributes.is_successful,
    locked: attributes.is_locked,
});

const validateName = (name: string): string | undefined =>
    name.length <= 191 ? undefined : 'The backup name must not exceed 191 characters.';

const useBackupsPageModel = () => pageStatus(useServerBackups(useLoadedServer().attributes.uuid, usePageSearch()));

function useBackupListModel(): BackupListModel {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const page = usePageSearch();
    const { uuid, identifier, feature_limits: limits } = useLoadedServer().attributes;
    const { data } = useServerBackups(uuid, page);
    const [create, download, restore, remove] = usePermissions([
        'backup.create',
        'backup.download',
        'backup.restore',
        'backup.delete',
    ]);
    const { mutateAsync: createBackup } = useCreateServerBackup();
    const { mutateAsync: downloadUrl, isPending: downloading } = useBackupDownloadUrl();
    const { mutateAsync: deleteBackup, isPending: deleting } = useDeleteServerBackup();
    const { mutateAsync: restoreBackup, isPending: restoring } = useRestoreServerBackup();
    const { mutateAsync: toggleLock, isPending: locking } = useToggleServerBackupLock(page);

    useWebsocketEvent(SocketEvent.BACKUP_COMPLETED, (payload) => {
        const completed = parseBackupCompletedPayload(payload);

        if (!completed) {
            console.warn('Ignoring a malformed backup completion event.', payload);

            return;
        }

        updateServerBackup(queryClient, uuid, page, completed.uuid, (backup) =>
            completeBackup(backup, completed, new Date().toISOString())
        );
    });

    const actions = useMemo(() => {
        const find = (backupUuid: string) => data?.data.find((backup) => backup.attributes.uuid === backupUuid);
        const withBackup = async (backupUuid: string, act: (backup: ServerBackup) => Promise<unknown>) => {
            const backup = find(backupUuid);

            return backup ? attempt(() => act(backup)) : false;
        };

        return {
            setPage: (next: number) =>
                void navigate({
                    to: '/server/$id/backups',
                    params: { id: identifier },
                    search: getPageSearch(next),
                    replace: true,
                    viewTransition: false,
                }),
            create: (values: BackupFormValues) => attempt(() => createBackup(createServerBackupInput(uuid, values))),
            download: async (backupUuid: string) => {
                await withBackup(backupUuid, async (backup) => {
                    window.location.assign(await downloadUrl(backupDownloadUrlInput(uuid, backup)));
                });
            },
            remove: (backupUuid: string) =>
                withBackup(backupUuid, async (backup) => {
                    await deleteBackup(deleteServerBackupInput(uuid, backup));
                    toast.success('Backup deleted', { description: `${backup.attributes.name} has been removed.` });
                }),
            restore: (backupUuid: string, truncate: boolean) =>
                withBackup(backupUuid, async (backup) => {
                    await restoreBackup(restoreServerBackupInput(uuid, backup, truncate));
                    toast.success('Backup restore started', {
                        description: `${backup.attributes.name} is being restored.`,
                    });
                }),
            toggleLock: (backupUuid: string) =>
                withBackup(backupUuid, (backup) => toggleLock(toggleServerBackupLockInput(uuid, backup))),
        };
    }, [data, navigate, identifier, uuid, createBackup, downloadUrl, deleteBackup, restoreBackup, toggleLock]);

    return useMemo(() => {
        const pagination = data?.meta.pagination;

        return {
            backups: (data?.data ?? []).map(backupSummary),
            pagination: {
                page,
                perPage: pagination?.per_page ?? 20,
                total: pagination?.total ?? 0,
                count: pagination?.count ?? 0,
            },
            limit: limits.backups ?? 0,
            count: data?.meta.backup_count ?? 0,
            permissions: { create, download, restore, delete: remove },
            pending: { download: downloading, remove: deleting, restore: restoring, lock: locking },
            validateName,
            actions,
        };
    }, [
        data,
        page,
        limits.backups,
        create,
        download,
        restore,
        remove,
        downloading,
        deleting,
        restoring,
        locking,
        actions,
    ]);
}

const succeeded = async () => true;
const noop = () => {};

const backupFixture = (options: Partial<BackupSummary> = {}): BackupSummary => ({
    uuid: 'b1b2b3b4-0000-4000-8000-000000000001',
    name: 'Before the 1.21 upgrade',
    checksum: 'sha1:3f786850e387550fdab836ed7e6dc881de23001b',
    bytes: 1536 * 1024 ** 2,
    createdAt: '2026-10-10T18:30:00Z',
    completedAt: '2026-10-10T18:34:12Z',
    successful: true,
    locked: false,
    ...options,
});

const backupFixtures: readonly BackupSummary[] = [
    backupFixture({
        uuid: 'b1b2b3b4-0000-4000-8000-000000000004',
        name: 'Nightly backup',
        checksum: null,
        bytes: 0,
        createdAt: '2026-10-12T04:00:00Z',
        completedAt: null,
    }),
    backupFixture({
        uuid: 'b1b2b3b4-0000-4000-8000-000000000003',
        name: 'Weekly world snapshot',
        createdAt: '2026-10-11T04:00:00Z',
        completedAt: '2026-10-11T04:06:40Z',
        bytes: 2048 * 1024 ** 2,
        locked: true,
    }),
    backupFixture({
        uuid: 'b1b2b3b4-0000-4000-8000-000000000002',
        name: 'Plugin test',
        checksum: null,
        bytes: 0,
        createdAt: '2026-10-10T21:00:00Z',
        completedAt: '2026-10-10T21:00:05Z',
        successful: false,
    }),
    backupFixture(),
];

const listFixture = (options: Partial<BackupListModel> = {}): BackupListModel => ({
    backups: backupFixtures,
    pagination: { page: 1, perPage: 20, total: backupFixtures.length, count: backupFixtures.length },
    limit: 5,
    count: 3,
    permissions: { create: true, download: true, restore: true, delete: true },
    pending: { download: false, remove: false, restore: false, lock: false },
    validateName,
    actions: {
        setPage: noop,
        create: succeeded,
        download: async () => {},
        remove: succeeded,
        restore: succeeded,
        toggleLock: succeeded,
    },
    ...options,
});

implementViews({
    'page.server.backups': {
        use: useBackupsPageModel,
        fixtures: pageStatusFixtures,
    },
    'server.backups': {
        use: useBackupListModel,
        fixtures: {
            backups: () => listFixture(),
            empty: () =>
                listFixture({ backups: [], pagination: { page: 1, perPage: 20, total: 0, count: 0 }, count: 0 }),
            full: () => listFixture({ limit: 3 }),
            noLimit: () =>
                listFixture({
                    backups: [],
                    pagination: { page: 1, perPage: 20, total: 0, count: 0 },
                    limit: 0,
                    count: 0,
                }),
            paged: () =>
                listFixture({
                    pagination: { page: 2, perPage: 4, total: 9, count: backupFixtures.length },
                    limit: 10,
                    count: 8,
                }),
            pastEnd: () =>
                listFixture({
                    backups: [],
                    pagination: { page: 4, perPage: 20, total: 9, count: 0 },
                    limit: 10,
                    count: 8,
                }),
            readOnly: () =>
                listFixture({ permissions: { create: false, download: false, restore: false, delete: false } }),
        },
    },
});
