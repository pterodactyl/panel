/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import type { ReactNode } from 'react';
import type { AxiosAdapter } from 'axios';
import { toast } from 'sonner';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { serverSubusersQueryOptions, type Subuser } from '@/api/server/users/queries';
import { systemPermissionsQueryOptions } from '@/api/system/queries';
import {
    commitExtensionRegistryBatch,
    createExtensionRegistryBatch,
    prepareExtensions,
    registerSlotComponent,
    type SlotComponentProps,
    type SubuserPermissionsSlotData,
} from '@/extensions/registry';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import type { TestServerOptions } from '@/views/fixtures';
import { View, ViewArea } from '@/views/View';

const permissions = {
    websocket: { description: 'Connect to the console.', keys: { connect: 'Stream the console.' } },
    control: {
        description: 'Control the power state.',
        keys: { console: 'Send commands.', start: 'Start the server.', stop: 'Stop the server.' },
    },
    backup: { description: 'Manage backups.', keys: { create: 'Create backups.', read: 'View backups.' } },
};

const subuser = (uuid: string, email: string, granted: string[]): Subuser => ({
    object: 'server_subuser',
    attributes: {
        uuid,
        identifier: uuid.slice(0, 8),
        username: email.split('@')[0],
        email,
        image: `https://www.gravatar.com/avatar/${uuid}`,
        '2fa_enabled': false,
        created_at: '2026-10-01T00:00:00Z',
        permissions: granted,
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

        return {
            data:
                config.method === 'post' ? subuser('99999999-9999-4999-8999-999999999999', 'new@example.test', []) : '',
            status: 200,
            statusText: 'OK',
            headers: {},
            config,
        };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    http.defaults.adapter = adapter;
    vi.restoreAllMocks();
});

async function renderInServer(children: ReactNode, options: TestServerOptions = {}, path = '/server/a1b2c3d4/users') {
    const server = createTestServer({ identifier: 'a1b2c3d4', ...options });

    host = createExtensionTestHost({ server, path });
    host.queryClient.setQueryData(systemPermissionsQueryOptions.queryKey, {
        object: 'system_permissions',
        attributes: { permissions },
    });
    host.queryClient.setQueryData(serverSubusersQueryOptions(server.attributes.uuid).queryKey, {
        object: 'list',
        data: [
            subuser('test-user', 'alex@example.test', ['websocket.connect']),
            subuser('22222222-2222-4222-8222-222222222222', 'sam@example.test', ['control.start', 'backup.read']),
        ],
    });

    await act(async () => {
        render(<ViewArea area='server'>{children}</ViewArea>, { wrapper: host!.Wrapper });
    });

    return server;
}

describe('server.subusers', () => {
    it('offers actions for everyone but the viewer', async () => {
        await renderInServer(<View name='server.subusers' />);

        expect(screen.getByRole('button', { name: 'Edit sam@example.test' })).toBeVisible();
        expect(screen.queryByRole('button', { name: 'Edit alex@example.test' })).not.toBeInTheDocument();
        expect(screen.getByRole('link', { name: 'New user' })).toHaveAttribute('href', '/server/a1b2c3d4/users/new');
    });

    it('removes a subuser and says who lost access', async () => {
        const success = vi.spyOn(toast, 'success');
        const server = await renderInServer(<View name='server.subusers' />);

        fireEvent.click(screen.getByRole('button', { name: 'Delete sam@example.test' }));
        await act(async () => {
            fireEvent.click(await screen.findByRole('button', { name: 'Yes, remove subuser' }));
        });

        await waitFor(() => expect(screen.queryByText('sam@example.test')).not.toBeInTheDocument());
        expect(requests).toContainEqual({
            method: 'delete',
            url: `/api/client/servers/${server.attributes.uuid}/users/22222222-2222-4222-8222-222222222222`,
            body: undefined,
        });
        expect(success).toHaveBeenCalledWith('Subuser removed', {
            description: 'sam@example.test no longer has access to this server.',
        });
    });

    it('edits a subuser in a dialog', async () => {
        const server = await renderInServer(<View name='server.subusers' />);

        fireEvent.click(screen.getByRole('button', { name: 'Edit sam@example.test' }));
        const dialog = await screen.findByRole('dialog');

        expect(within(dialog).getByRole('heading', { name: 'Modify permissions for sam@example.test' })).toBeVisible();
        fireEvent.click(within(dialog).getByRole('checkbox', { name: /^stop/ }));
        await act(async () => {
            fireEvent.click(within(dialog).getAllByRole('button', { name: 'Save' })[0]);
        });

        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${server.attributes.uuid}/users/22222222-2222-4222-8222-222222222222`,
            body: { permissions: ['control.start', 'backup.read', 'control.stop'] },
        });
    });
});

describe('server.subuserForm', () => {
    it('limits a subuser to the permissions they hold and keeps the slot to that', async () => {
        let slot: SubuserPermissionsSlotData | undefined;

        prepareExtensions([{ id: 'presets', entry: '/presets.js' }]);
        const batch = createExtensionRegistryBatch();

        registerSlotComponent(
            'presets',
            'server.users.permissions.before',
            ({ data }: SlotComponentProps) => {
                slot = data as SubuserPermissionsSlotData;

                return null;
            },
            batch
        );
        commitExtensionRegistryBatch('presets', batch);

        await renderInServer(
            <View name='server.subuserForm' />,
            { owner: false, permissions: ['user.create', 'control.start', 'backup.read'] },
            '/server/a1b2c3d4/users/new'
        );

        expect(screen.getByText(/Only permissions which your account is currently assigned/)).toBeVisible();
        expect(screen.getByRole('checkbox', { name: /^stop/ })).toHaveAttribute('aria-disabled', 'true');
        expect(slot).toMatchObject({
            mode: 'create',
            selectedPermissions: [],
            editablePermissions: ['control.start', 'backup.read'],
            disabled: false,
        });

        await act(async () => slot!.setPermissions(['control.start', 'control.stop', 'backup.read']));

        expect(screen.getByRole('checkbox', { name: /^start/ })).toBeChecked();
        expect(screen.getByRole('checkbox', { name: /^read/ })).toBeChecked();
        expect(screen.getByRole('checkbox', { name: /^stop/ })).not.toBeChecked();
    });

    it('invites a user and returns to the list', async () => {
        const success = vi.spyOn(toast, 'success');
        const server = await renderInServer(<View name='server.subuserForm' />, {}, '/server/a1b2c3d4/users/new');

        fireEvent.change(screen.getByLabelText('User Email'), { target: { value: 'new@example.test' } });
        fireEvent.click(screen.getByRole('checkbox', { name: 'Select all control permissions' }));
        await act(async () => {
            fireEvent.click(screen.getAllByRole('button', { name: 'Invite User' })[0]);
        });

        await waitFor(() => expect(host!.location().pathname).toBe('/server/a1b2c3d4/users'));
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${server.attributes.uuid}/users`,
            body: { email: 'new@example.test', permissions: ['control.console', 'control.start', 'control.stop'] },
        });
        expect(success).toHaveBeenCalledWith('Subuser invited', { description: 'new@example.test has been invited.' });
    });
});
