// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type {
    ClientCreateServerBackupData,
    ClientListServerBackupsResponse,
    ClientRestoreServerBackupData,
    Options,
} from '@/api/generated';
import { useCreateServerBackup, useRestoreServerBackup, type ServerBackup } from '@/api/server/backups/queries';

const mocks = vi.hoisted(() => ({
    create: vi.fn(),
    restore: vi.fn(),
    updateCurrentServer: vi.fn(),
    notifyError: vi.fn(),
    toastSuccess: vi.fn(),
}));

const backupQueryKey = (input: { path: { server_uuid: string }; query?: { page?: number } }) => {
    const key = { _id: 'clientListServerBackups', path: input.path };
    return input.query ? [{ ...key, query: input.query }] : [key];
};
type BackupListInput = Parameters<typeof backupQueryKey>[0];

vi.mock('sonner', () => ({ toast: { success: mocks.toastSuccess } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.notifyError }));
vi.mock('@/api/server/queries', () => ({ useUpdateCurrentServer: () => mocks.updateCurrentServer }));
vi.mock('@/api/generated', () => ({ clientGetBackupDownloadUrl: vi.fn() }));
vi.mock('@/api/generated/@tanstack/react-query.gen', () => ({
    clientCreateServerBackupMutation: () => ({ mutationFn: mocks.create }),
    clientDeleteServerBackupMutation: () => ({ mutationFn: vi.fn() }),
    clientListServerBackupsOptions: (input: BackupListInput) => ({
        queryKey: backupQueryKey(input),
        queryFn: vi.fn(),
    }),
    clientListServerBackupsQueryKey: (input: BackupListInput) => backupQueryKey(input),
    clientRestoreServerBackupMutation: () => ({ mutationFn: mocks.restore }),
    clientToggleBackupLockMutation: () => ({ mutationFn: vi.fn() }),
}));

const backup = (uuid: string, name: string): ServerBackup =>
    ({ object: 'backup', attributes: { uuid, name, is_locked: false } }) as ServerBackup;

const setup = () => {
    const queryClient = new QueryClient({ defaultOptions: { mutations: { retry: false }, queries: { retry: false } } });
    const wrapper = ({ children }: PropsWithChildren) => (
        <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
    );

    return { queryClient, wrapper };
};

describe('server backup mutations', () => {
    beforeEach(() => vi.clearAllMocks());

    it('invalidates server backup pages without inserting a new item into an arbitrary page', async () => {
        const { queryClient, wrapper } = setup();
        const created = backup('new', 'nightly');
        const queryKey = backupQueryKey({ path: { server_uuid: 'server' }, query: { page: 3 } });
        mocks.create.mockResolvedValue(created);
        queryClient.setQueryData<ClientListServerBackupsResponse>(queryKey, {
            object: 'list',
            data: [],
            meta: {
                backup_count: 0,
                pagination: { total: 0, count: 0, per_page: 20, current_page: 3, total_pages: 1 },
            },
        });
        const { result } = renderHook(() => useCreateServerBackup(), { wrapper });
        const input = { path: { server_uuid: 'server' } } as Options<ClientCreateServerBackupData>;

        await act(async () => result.current.mutateAsync(input));

        const cached = queryClient.getQueryData<ClientListServerBackupsResponse>(queryKey);
        expect(cached?.data).toEqual([]);
        expect(queryClient.getQueryState(queryKey)?.isInvalidated).toBe(true);
        expect(mocks.create).toHaveBeenCalledWith(input, expect.anything());
        expect(mocks.toastSuccess).toHaveBeenCalledOnce();
    });

    it('updates current server state before notifying when a restore starts', async () => {
        const { wrapper } = setup();
        const selected = backup('backup', 'before-upgrade');
        mocks.restore.mockResolvedValue(undefined);
        const { result } = renderHook(() => useRestoreServerBackup(selected), { wrapper });

        await act(async () =>
            result.current.mutateAsync({
                path: { server_uuid: 'server', backup_uuid: 'backup' },
                body: { truncate: false },
            } as Options<ClientRestoreServerBackupData>)
        );

        expect(mocks.updateCurrentServer).toHaveBeenCalledOnce();
        expect(mocks.toastSuccess).toHaveBeenCalledWith('Backup restore started', {
            description: 'before-upgrade is being restored.',
        });
        expect(mocks.updateCurrentServer.mock.invocationCallOrder[0]).toBeLessThan(
            mocks.toastSuccess.mock.invocationCallOrder[0] ?? Number.MAX_SAFE_INTEGER
        );
    });
});
