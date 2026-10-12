/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import type { AxiosAdapter } from 'axios';
import { toast } from 'sonner';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { serverBackupsQueryOptions, type ServerBackup } from '@/api/server/backups/queries';
import { serverQueryOptions } from '@/api/server/queries';
import { SocketEvent } from '@/components/server/events';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { getViewDefinition, type ViewModels } from '@/views/registry';
import { View, ViewArea, ViewFixtureProvider } from '@/views/View';

const base = createTestServer({ identifier: 'a1b2c3d4' });
const server = {
    ...base,
    attributes: { ...base.attributes, feature_limits: { ...base.attributes.feature_limits, backups: 5 } },
};
const { uuid } = server.attributes;

const backup = (id: string, name: string, options: Partial<ServerBackup['attributes']> = {}): ServerBackup => ({
    object: 'backup',
    attributes: {
        uuid: id,
        is_successful: true,
        is_locked: false,
        name,
        ignored_files: [],
        checksum: 'sha1:abc',
        bytes: 1024 ** 2,
        created_at: '2026-10-10T18:30:00Z',
        completed_at: '2026-10-10T18:34:00Z',
        ...options,
    },
});

let host: ExtensionTestHost | undefined;
let requests: { method: string; url: string; body: unknown }[];
const adapter = http.defaults.adapter;

beforeEach(() => {
    requests = [];
    http.defaults.adapter = (async (config) => {
        const body: unknown = config.data ? JSON.parse(String(config.data)) : undefined;

        requests.push({ method: config.method ?? 'get', url: config.url ?? '', body });

        return { data: '', status: 204, statusText: 'No Content', headers: {}, config };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    http.defaults.adapter = adapter;
    vi.restoreAllMocks();
});

async function renderBackups(children: ReactNode, backups: ServerBackup[]) {
    host = createExtensionTestHost({ server, path: '/server/a1b2c3d4/backups' });
    host.queryClient.setQueryData(serverBackupsQueryOptions(uuid, 1).queryKey, {
        object: 'list',
        data: backups,
        meta: {
            backup_count: backups.length,
            pagination: { total: backups.length, count: backups.length, per_page: 20, current_page: 1, total_pages: 1 },
        },
    });

    await act(async () => {
        render(<ViewArea area='server'>{children}</ViewArea>, { wrapper: host!.Wrapper });
    });
}

describe('server.backups', () => {
    it('completes a running backup as the node reports it', async () => {
        await renderBackups(<View name='server.backups' />, [
            backup('running', 'Nightly backup', { completed_at: null, checksum: null, bytes: 0 }),
        ]);

        expect(screen.getByText('1 of 5 backups have been created for this server.')).toBeVisible();
        expect(screen.queryByRole('button', { name: 'Delete Nightly backup' })).not.toBeInTheDocument();

        act(() =>
            host!.emitWebsocket(
                SocketEvent.BACKUP_COMPLETED,
                JSON.stringify({ uuid: 'running', is_successful: true, checksum: 'f00', checksum_type: 'sha1' })
            )
        );

        expect(await screen.findByText('sha1:f00')).toBeVisible();
        expect(screen.getByRole('button', { name: 'Delete Nightly backup' })).toBeVisible();
    });

    it('deletes a backup once confirmed and names it', async () => {
        const success = vi.spyOn(toast, 'success');

        await renderBackups(<View name='server.backups' />, [backup('b1', 'World')]);

        fireEvent.click(screen.getByRole('button', { name: 'Delete World' }));
        await act(async () => {
            fireEvent.click(within(await screen.findByRole('dialog')).getByRole('button', { name: 'Continue' }));
        });

        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(requests).toContainEqual({
            method: 'delete',
            url: `/api/client/servers/${uuid}/backups/b1`,
            body: undefined,
        });
        expect(success).toHaveBeenCalledWith('Backup deleted', { description: 'World has been removed.' });
    });

    it('restores a backup, deleting files first when asked, and marks the server restoring', async () => {
        const success = vi.spyOn(toast, 'success');

        await renderBackups(<View name='server.backups' />, [backup('b1', 'World')]);

        fireEvent.click(screen.getByRole('button', { name: 'More actions for World' }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Restore' }));
        const dialog = await screen.findByRole('dialog');

        fireEvent.click(within(dialog).getByRole('checkbox'));
        await act(async () => {
            fireEvent.click(within(dialog).getByRole('button', { name: 'Restore' }));
        });

        await waitFor(() => expect(success).toHaveBeenCalledWith('Backup restore started', expect.anything()));
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${uuid}/backups/b1/restore`,
            body: { truncate: true },
        });
        expect(host!.queryClient.getQueryData(serverQueryOptions('a1b2c3d4').queryKey)).toMatchObject({
            attributes: { status: 'restoring_backup' },
        });
    });

    it('asks before unlocking a locked backup, and cannot delete it', async () => {
        await renderBackups(<View name='server.backups' />, [backup('b1', 'World', { is_locked: true })]);

        expect(screen.getByRole('button', { name: 'Delete World' })).toBeDisabled();
        fireEvent.click(screen.getByRole('button', { name: 'More actions for World' }));
        fireEvent.click(await screen.findByRole('menuitem', { name: 'Unlock' }));

        expect(within(await screen.findByRole('dialog')).getByText('Unlock "World"')).toBeVisible();
        expect(requests).toEqual([]);
    });
});

describe('classic server.backups', () => {
    const model = (fixture: string) =>
        getViewDefinition('server.backups')!.fixtures![fixture]!() as ViewModels['server.backups'];

    async function renderFixture(value: ViewModels['server.backups']) {
        host = createExtensionTestHost({ server, path: '/server/a1b2c3d4/backups' });

        await act(async () => {
            render(
                <ViewArea area='server'>
                    <ViewFixtureProvider models={{ 'server.backups': value }}>
                        <View name='server.backups' />
                    </ViewFixtureProvider>
                </ViewArea>,
                { wrapper: host!.Wrapper }
            );
        });
    }

    it('pages through the backups', async () => {
        const setPage = vi.fn();
        const paged = model('paged');

        await renderFixture({ ...paged, actions: { ...paged.actions, setPage } });

        expect(screen.getByLabelText('Showing 5 to 8 of 9 backups')).toBeVisible();
        fireEvent.click(screen.getByRole('button', { name: 'Next page' }));
        expect(setPage).toHaveBeenCalledWith(3);
    });

    it('offers the first page past the end of the list', async () => {
        const setPage = vi.fn();
        const pastEnd = model('pastEnd');

        await renderFixture({ ...pastEnd, actions: { ...pastEnd.actions, setPage } });

        expect(screen.getByText('No backups on this page')).toBeVisible();
        fireEvent.click(screen.getByText('First page'));
        expect(setPage).toHaveBeenCalledWith(1);
    });

    it('explains a backup limit of zero', async () => {
        await renderFixture(model('noLimit'));

        expect(screen.getByText("Backups can't be created because this server's backup limit is 0.")).toBeVisible();
        expect(screen.queryByRole('button', { name: 'New backup' })).not.toBeInTheDocument();
    });
});
