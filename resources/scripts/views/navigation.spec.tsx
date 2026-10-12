/** @vitest-environment jsdom */
import type { ReactElement } from 'react';
import { act, cleanup, render, screen } from '@testing-library/react';
import { afterEach, beforeAll, expect, it } from 'vitest';
import {
    commitExtensionRegistryBatch,
    createExtensionRegistryBatch,
    getExtensionScreens,
    prepareExtensions,
    registerScreen,
    type ScreenOptions,
} from '@/extensions/registry';
import { publishAreaNav } from '@/router/nav';
import {
    createExtensionTestHost,
    createTestServer,
    type ExtensionTestHost,
    type TestServerOptions,
} from '@/sdk/testing';
import {
    Navigation,
    NavigationLink,
    resourceNavigation,
    useActiveNavItem,
    useNavigation,
    type NavArea,
} from './navigation';

let host: ExtensionTestHost | undefined;

beforeAll(() => {
    prepareExtensions([
        {
            id: 'tabs',
            entry: '/tabs.js',
            screens: [
                {
                    id: 'mods',
                    area: 'server',
                    path: 'mods',
                    nav: { label: 'Mods' },
                    when: { eggTags: { any: ['Minecraft'] } },
                },
                {
                    id: 'domains',
                    area: 'server',
                    path: 'domains',
                    nav: { label: 'Subdomains', icon: 'globe', group: 'Network' },
                    when: { runtime: true },
                },
                { id: 'tickets', area: 'server', path: 'tickets', nav: { label: 'Tickets', badge: 'New' } },
                { id: 'logs', area: 'server', path: 'logs', nav: { label: 'Logs' }, permission: ['backup.read'] },
                { id: 'probe', area: 'admin', parent: 'admin.node', path: 'probe', nav: { label: 'Probe' } },
            ],
        },
    ]);
    publishAreaNav('server', [
        { segment: '', label: 'Console', exact: true },
        { segment: 'files', label: 'Files', permission: 'file.*', hash: '/' },
        { segment: 'backups', label: 'Backups', permission: 'backup.*' },
        // As the route tree publishes extension screens.
        ...getExtensionScreens('server').map((screen) => ({
            label: screen.nav!.label,
            icon: screen.nav!.icon,
            group: screen.nav!.group,
            permission: screen.permission,
            screen,
            segment: screen.path,
        })),
    ]);
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
});

const subuser = (options: TestServerOptions = {}) =>
    createTestServer({
        identifier: 'abc',
        owner: false,
        permissions: ['file.read'],
        eggTags: ['palworld'],
        ...options,
    });

async function renderServer(children: ReactElement, server = subuser(), path = '/server/abc') {
    host = createExtensionTestHost({ server, path });

    await act(async () => {
        render(children, { wrapper: host!.Wrapper });
    });
}

function Items({ area }: { area: NavArea }) {
    const { items } = useNavigation(area);
    const active = useActiveNavItem(items);

    return (
        <ul>
            {items.map((item) => (
                <li key={item.id} data-active={item === active || undefined}>
                    {JSON.stringify(item)}
                </li>
            ))}
        </ul>
    );
}

const listed = () => screen.getAllByRole('listitem').map((entry) => JSON.parse(entry.textContent ?? '{}'));

function load(options: Record<string, ScreenOptions>) {
    const batch = createExtensionRegistryBatch();

    for (const id of ['mods', 'domains', 'tickets', 'logs', 'probe']) {
        registerScreen('tabs', id, async () => ({ default: () => null }), batch, options[id]);
    }

    act(() => commitExtensionRegistryBatch('tabs', batch));
}

it('lists the server items the subuser may open and whose egg rules match', async () => {
    await renderServer(<Items area='server' />);

    expect(listed().map((item) => item.id)).toEqual(['index', 'files', 'domains', 'tickets']);
    expect(listed()[1]).toEqual({
        id: 'files',
        label: 'Files',
        to: '/server/$id/files',
        params: { id: 'abc' },
        hash: '/',
        icon: null,
        group: null,
        order: 1,
        exact: false,
    });
    expect(listed()[2]).toMatchObject({
        id: 'domains',
        extensionId: 'tabs',
        icon: 'globe',
        group: 'Network',
        order: 4,
    });
    cleanup();
    host?.dispose();

    await renderServer(<Items area='server' />, subuser({ permissions: ['backup.read'], eggTags: ['minecraft'] }));

    expect(listed().map((item) => item.id)).toEqual(['index', 'backups', 'mods', 'domains', 'tickets', 'logs']);
});

it('picks the most specific active item', async () => {
    await renderServer(<Items area='server' />, subuser({ owner: true }), '/server/abc/files');

    expect(screen.getAllByRole('listitem').find((entry) => entry.dataset.active)?.textContent).toContain('"files"');
});

it('renders gated items once their predicate allows them, with their live badge', async () => {
    let visible = false;

    await renderServer(
        <nav>
            <Navigation area='server'>
                {(item, state) => (
                    <NavigationLink item={item} data-active={state.active || undefined}>
                        {item.label} {state.badge}
                    </NavigationLink>
                )}
            </Navigation>
        </nav>,
        subuser(),
        '/server/abc/files'
    );

    expect(screen.getByRole('link', { name: 'Console' })).toHaveAttribute('href', '/server/abc');
    expect(screen.getByRole('link', { name: 'Files' })).toHaveAttribute('data-active');
    expect(screen.getByRole('link', { name: 'Files' })).toHaveAttribute('data-status', 'active');
    expect(screen.getByRole('link', { name: 'Tickets New' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'Subdomains' })).not.toBeInTheDocument();

    load({ domains: { visible: () => visible }, tickets: { badge: () => 3 } });

    expect(await screen.findByRole('link', { name: 'Tickets 3' })).toBeVisible();
    expect(screen.queryByRole('link', { name: 'Subdomains' })).not.toBeInTheDocument();

    visible = true;
    await act(async () => {
        await host!.navigate({ to: '/server/$id', params: { id: 'abc' } });
    });

    expect(await screen.findByRole('link', { name: 'Subdomains' })).toHaveAttribute('href', '/server/abc/domains');
});

it('builds admin resource tabs from its own and the extension screens beneath it', () => {
    const items = resourceNavigation('admin.node', { id: '42' }, [
        { id: 'about', label: 'About', to: '/panel/nodes/$id', exact: true },
    ]);

    expect(items).toEqual([
        {
            id: 'about',
            label: 'About',
            to: '/panel/nodes/$id',
            params: { id: '42' },
            icon: null,
            group: null,
            order: 0,
            exact: true,
        },
        {
            id: 'probe',
            label: 'Probe',
            to: '/panel/nodes/$id/probe',
            params: { id: '42' },
            icon: null,
            group: null,
            order: 1,
            exact: false,
            extensionId: 'tabs',
        },
    ]);
});
