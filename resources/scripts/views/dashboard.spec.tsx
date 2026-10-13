/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import type { ReactNode } from 'react';
import { afterEach, beforeAll, expect, it, vi } from 'vitest';
import type * as ServerQueries from '@/api/account/servers/queries';
import { createExtensionTestHost, createTestServer, type ExtensionTestHost } from '@/sdk/testing';
import { registerClassicTheme } from './classic';
import { ViewFixtureProvider, View, ViewArea } from './View';
import { clearThemeLayers, registerViewLayer, type ViewProps } from './registry';
import './dashboard';

const useServerResourceUsage = vi.hoisted(() => vi.fn((_uuid: string, _enabled?: boolean) => ({ data: undefined })));

vi.mock('@/api/account/servers/queries', async (importOriginal) => ({
    ...(await importOriginal<typeof ServerQueries>()),
    useServerResourceUsage,
}));

let host: ExtensionTestHost | undefined;

beforeAll(() => registerClassicTheme());

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    useServerResourceUsage.mockClear();
});

async function renderInApp(children: ReactNode) {
    host = createExtensionTestHost({ path: '/' });
    const { Wrapper } = host;

    await act(async () => {
        render(<ViewArea area='app'>{children}</ViewArea>, { wrapper: Wrapper });
    });
}

it('shows a maintenance badge instead of loading stats when the node is under maintenance', async () => {
    const server = createTestServer({ name: 'Survival' });

    await renderInApp(
        <View
            name='dashboard.serverCard'
            server={{ ...server, attributes: { ...server.attributes, is_node_under_maintenance: true } }}
        />
    );

    expect(await screen.findByText('Under Maintenance')).toBeVisible();
    expect(useServerResourceUsage).toHaveBeenCalledWith(server.attributes.uuid, false);
});

it('loads stats for a server on an available node', async () => {
    const server = createTestServer({ name: 'Survival' });

    await renderInApp(<View name='dashboard.serverCard' server={server} />);

    expect(await screen.findByText('Survival')).toBeVisible();
    expect(screen.queryByText('Under Maintenance')).not.toBeInTheDocument();
    expect(useServerResourceUsage).toHaveBeenCalledWith(server.attributes.uuid, true);
});

it('gives owners power actions from list data, which carries no per-server meta', async () => {
    registerViewLayer('theme', 'theme', {
        'dashboard.serverCard': ({ model }: ViewProps<'dashboard.serverCard'>) => (
            <span>start: {String(model.permissions.start)}</span>
        ),
    });
    const server = createTestServer({ name: 'Survival' });

    try {
        await renderInApp(
            <View
                name='dashboard.serverCard'
                server={{ ...server, meta: undefined, attributes: { ...server.attributes, server_owner: true } }}
            />
        );

        expect(await screen.findByText('start: true')).toBeVisible();
    } finally {
        clearThemeLayers();
    }
});

it('renders the classic server list from a fixture, with a link and power state per row', async () => {
    await renderInApp(
        <ViewFixtureProvider fixtures={{ 'dashboard.serverList': 'servers' }}>
            <View name='dashboard.serverList' />
        </ViewFixtureProvider>
    );

    const survival = await screen.findByRole('link', { name: 'Survival' });

    expect(survival).toHaveAttribute('href', '/server/a1b2c3d4');
    expect(survival).toHaveAttribute('data-status', 'success');
    expect(survival.querySelector('[data-part="status"]')).toHaveClass('bg-success');
    expect(screen.getByRole('link', { name: 'Creative' }).querySelector('[data-part="status"]')).toHaveClass(
        'bg-destructive'
    );
    expect(screen.getByRole('switch')).toBeInTheDocument();
});

it('shows the empty state and the dashboard error from fixtures', async () => {
    await renderInApp(
        <ViewFixtureProvider fixtures={{ 'dashboard.serverList': 'empty', 'page.dashboard': 'error' }}>
            <View name='dashboard.serverList' />
            <View name='page.dashboard' />
        </ViewFixtureProvider>
    );

    expect(await screen.findByText('No servers yet')).toBeVisible();
    expect(screen.queryByRole('switch')).not.toBeInTheDocument();
    expect(screen.getByText('Request failed with status code 500.')).toBeVisible();
});
