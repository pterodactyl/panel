/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor } from '@testing-library/react';
import type { ReactNode } from 'react';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import http from '@/api/http';
import { serverQueryOptions } from '@/api/server/queries';
import type { Server } from '@/api/server/types';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { SKIPPED_INSTALL_SCRIPT_MESSAGE } from '@/lib/serverStatus';
import { View, ViewArea } from '@/views/View';

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
});

type Attributes = Partial<Server['attributes']>;

async function renderInServer(children: ReactNode, attributes: Attributes = {}, permissions?: string[]) {
    const base = createTestServer({ identifier: 'a1b2c3d4', owner: !permissions, permissions });
    const server = { ...base, attributes: { ...base.attributes, ...attributes } };

    host = createExtensionTestHost({ server });
    await act(async () => {
        render(<ViewArea area='server'>{children}</ViewArea>, { wrapper: host!.Wrapper });
    });

    return server;
}

describe('server settings', () => {
    it('shows the SFTP details of the viewer', async () => {
        await renderInServer(<View name='server.sftp' />, { sftp_details: { ip: '2001:db8::10', port: 2022 } });

        expect(screen.getByDisplayValue('sftp://[2001:db8::10]:2022')).toBeVisible();
        expect(screen.getByDisplayValue('Alex.a1b2c3d4')).toBeVisible();
        expect(screen.getByRole('link', { name: 'Launch SFTP' })).toHaveAttribute(
            'href',
            'sftp://Alex.a1b2c3d4@[2001:db8::10]:2022'
        );
    });

    it('leaves out the blocks the viewer may not use', async () => {
        await renderInServer(<View name='page.server.settings' />, {}, ['settings.rename']);

        expect(screen.queryByText('SFTP Details')).not.toBeInTheDocument();
        expect(screen.queryByText('Reinstall Server')).not.toBeInTheDocument();
        expect(screen.getByText('Change Server Details')).toBeVisible();
        expect(screen.getByText('Debug Information')).toBeVisible();
    });

    it('renames the server', async () => {
        const server = await renderInServer(<View name='server.rename' />, { name: 'Survival', description: null });

        fireEvent.change(screen.getByLabelText('Server Name'), { target: { value: 'Creative' } });
        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: 'Save' }));
        });

        await waitFor(() =>
            expect(host!.queryClient.getQueryData(serverQueryOptions('a1b2c3d4').queryKey)).toMatchObject({
                attributes: { name: 'Creative' },
            })
        );
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${server.attributes.uuid}/settings/rename`,
            body: { name: 'Creative', description: '' },
        });
    });

    it('explains why a server skipping its install script cannot be reinstalled', async () => {
        await renderInServer(<View name='server.reinstall' />, { skip_scripts: true });

        expect(screen.getByText(SKIPPED_INSTALL_SCRIPT_MESSAGE)).toBeVisible();
        expect(screen.getByRole('button', { name: 'Reinstall Server' })).toBeDisabled();
    });

    it('reinstalls the server once confirmed', async () => {
        const server = await renderInServer(<View name='server.reinstall' />);

        fireEvent.click(screen.getByRole('button', { name: 'Reinstall Server' }));
        await act(async () => {
            fireEvent.click(await screen.findByRole('button', { name: 'Yes, reinstall server' }));
        });

        await waitFor(() => expect(screen.queryByRole('dialog')).not.toBeInTheDocument());
        expect(requests).toContainEqual({
            method: 'post',
            url: `/api/client/servers/${server.attributes.uuid}/settings/reinstall`,
            body: undefined,
        });
    });
});

describe('server.conflict', () => {
    it.each([
        [{ status: 'install_failed' }, 'Running Installer'],
        [{ status: 'suspended' }, 'Server Suspended'],
        [{ status: null, is_node_under_maintenance: true }, 'Node under Maintenance'],
        [{ status: null, is_transferring: true }, 'Transferring'],
        [{ status: 'restoring_backup' }, 'Restoring from Backup'],
    ] satisfies [Attributes, string][])('names the conflict of %o', async (attributes, title) => {
        await renderInServer(<View name='server.conflict' />, attributes);

        expect(screen.getByRole('heading', { name: title })).toBeVisible();
    });

    it('renders nothing for a server without a conflict', async () => {
        await renderInServer(<View name='server.conflict' />);

        expect(screen.queryByRole('heading')).not.toBeInTheDocument();
    });
});
