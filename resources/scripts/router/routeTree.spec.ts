/** @vitest-environment jsdom */
import { QueryClient } from '@tanstack/react-query';
import { createMemoryHistory, createRouter } from '@tanstack/react-router';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { SiteExtensionEntry } from '@/extensions/registry';

const extensions: SiteExtensionEntry[] = [
    {
        id: 'collides',
        entry: '/collides.js',
        screens: [{ id: 'main', area: 'server', path: 'databases', nav: { label: 'Databases' } }],
    },
    {
        id: 'collides-admin',
        entry: '/collides-admin.js',
        screens: [{ id: 'main', area: 'admin', path: 'nodes/extra' }],
    },
    {
        id: 'healthy',
        entry: '/healthy.js',
        screens: [{ id: 'main', area: 'server', path: 'votes', nav: { label: 'Votes' } }],
    },
];

beforeEach(() => {
    vi.resetModules();
    vi.spyOn(console, 'error').mockImplementation(() => {});
    (window as Window & { SiteConfiguration?: unknown }).SiteConfiguration = { extensions };
});
afterEach(() => {
    delete (window as Window & { SiteConfiguration?: unknown }).SiteConfiguration;
});

it(
    'rejects extension screens that collide with core routes so the router still builds',
    { timeout: 20_000 },
    async () => {
        const routeTree = (await import('@/router/routeTree')).buildRouteTree();
        const registry = await import('@/extensions/registry');

        expect(registry.getLoadableExtensions()?.map((entry) => entry.id)).toEqual(['healthy']);
        expect(registry.getExtensionStates().find((state) => state.id === 'collides')?.error).toContain(
            'collides with core in server'
        );
        expect(registry.getExtensionStates().find((state) => state.id === 'collides-admin')?.error).toContain(
            'collides with core in admin'
        );
        expect(() =>
            createRouter({ routeTree, context: { queryClient: new QueryClient() }, history: createMemoryHistory() })
        ).not.toThrow();

        const { getAreaNav } = await import('@/router/nav');
        const tabs = getAreaNav('server');

        expect(tabs.find((tab) => tab.segment === 'votes')?.screen).toMatchObject({
            extensionId: 'healthy',
            id: 'main',
        });
        expect(tabs.find((tab) => tab.segment === 'files')?.screen).toBeUndefined();
    }
);

it('mounts extension tabs beneath the existing resource layouts and preserves core tabs', async () => {
    (window as Window & { SiteConfiguration?: unknown }).SiteConfiguration = {
        extensions: [
            {
                id: 'probe',
                entry: '/probe.js',
                screens: [
                    { id: 'node', area: 'admin', parent: 'admin.node', path: 'probe', nav: { label: 'Probe' } },
                    { id: 'server', area: 'admin', parent: 'admin.server', path: 'probe' },
                    { id: 'egg', area: 'admin', parent: 'admin.egg', path: 'probe' },
                    { id: 'user', area: 'admin', parent: 'admin.user', path: 'probe' },
                ],
            },
        ],
    };
    const routeTree = (await import('@/router/routeTree')).buildRouteTree();
    const router = createRouter({
        routeTree,
        context: { queryClient: new QueryClient() },
        history: createMemoryHistory(),
    });

    for (const resource of ['nodes', 'servers', 'eggs', 'users']) {
        const matches = router.matchRoutes(`/panel/${resource}/42/probe`);

        expect(matches.at(-1)?.pathname).toBe(`/panel/${resource}/42/probe`);
        expect(matches.at(-2)?.pathname).toBe(`/panel/${resource}/42`);
    }

    expect(router.matchRoutes('/panel/nodes/42/settings').at(-1)?.pathname).toBe('/panel/nodes/42/settings');
});

it('builds the tree from the extensions it is given', async () => {
    const { buildRouteTree } = await import('@/router/routeTree');
    const { getAreaNav } = await import('@/router/nav');
    const router = createRouter({
        routeTree: buildRouteTree([
            {
                id: 'given',
                entry: '/given.js',
                screens: [{ id: 'main', area: 'account', path: 'given', nav: { label: 'Given' } }],
            },
        ]),
        context: { queryClient: new QueryClient() },
        history: createMemoryHistory(),
    });

    expect(router.matchRoutes('/account/given').at(-1)?.routeId).toBe('/authenticated/account/given');
    expect(router.matchRoutes('/server/abc/votes').some((match) => match.routeId.endsWith('/votes'))).toBe(false);
    expect(getAreaNav('account').map((entry) => entry.segment)).toEqual(['', 'api', 'ssh', 'activity', 'given']);
});

type BootstrapWindow = Window & { PterodactylUser?: unknown; SiteConfiguration?: unknown };

async function loadRoute(path: string, options: { rootAdmin?: boolean; seed?: (client: QueryClient) => void } = {}) {
    (window as BootstrapWindow).PterodactylUser = {
        uuid: 'user',
        username: 'alex',
        email: 'alex@example.test',
        root_admin: options.rootAdmin ?? false,
        use_totp: false,
        language: 'en',
        updated_at: '2026-01-01T00:00:00Z',
        created_at: '2026-01-01T00:00:00Z',
    };
    (window as BootstrapWindow).SiteConfiguration = { name: 'Panel', locale: 'en', recaptcha: { enabled: false } };
    const { buildRouteTree } = await import('@/router/routeTree');
    const http = (await import('@/api/http')).default;
    const requests: string[] = [];

    http.defaults.adapter = async (config) => {
        requests.push(config.url ?? '');
        throw new Error(`unexpected request to ${config.url}`);
    };

    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: Infinity } } });

    options.seed?.(queryClient);
    const router = createRouter({
        routeTree: buildRouteTree([]),
        context: { queryClient },
        history: createMemoryHistory({ initialEntries: [path] }),
    });

    await router.load();
    delete (window as BootstrapWindow).PterodactylUser;

    return { router, requests, leaf: router.state.matches.at(-1)! };
}

it('sends guests to sign in with the page they asked for', { timeout: 20_000 }, async () => {
    (window as BootstrapWindow).SiteConfiguration = { name: 'Panel', locale: 'en', recaptcha: { enabled: false } };
    const { buildRouteTree } = await import('@/router/routeTree');
    const router = createRouter({
        routeTree: buildRouteTree([]),
        context: { queryClient: new QueryClient() },
        history: createMemoryHistory({ initialEntries: ['/panel/nodes/42/settings?tab=network'] }),
    });

    await router.load();

    expect(router.state.location.pathname).toBe('/auth/login');
    expect(router.state.location.search).toEqual({ redirect: '/panel/nodes/42/settings?tab=network' });
});

it('denies a server tab before its loader requests data the subuser cannot read', { timeout: 20_000 }, async () => {
    const { createTestServer } = await import('@/sdk/testing');
    const { serverQueryOptions } = await import('@/api/server/queries');
    const { RouteAccessDenied } = await import('@/router/RouteError');
    const { leaf, requests } = await loadRoute('/server/abc/databases', {
        seed: (client) =>
            client.setQueryData(
                serverQueryOptions('abc').queryKey,
                createTestServer({ identifier: 'abc', owner: false, permissions: ['control.console'] })
            ),
    });

    expect(leaf.routeId).toBe('/authenticated/server/$id/databases');
    expect(leaf.status).toBe('error');
    expect(leaf.error).toBeInstanceOf(RouteAccessDenied);
    expect(requests).toEqual([]);
});

it('loads a server tab for a subuser holding its permission', { timeout: 20_000 }, async () => {
    const { createTestServer } = await import('@/sdk/testing');
    const { serverQueryOptions } = await import('@/api/server/queries');
    const { serverDatabasesQueryOptions } = await import('@/api/server/databases/queries');
    const server = createTestServer({ identifier: 'abc', owner: false, permissions: ['database.read'] });
    const { leaf, requests } = await loadRoute('/server/abc/databases', {
        seed: (client) => {
            client.setQueryData(serverQueryOptions('abc').queryKey, server);
            client.setQueryData(serverDatabasesQueryOptions(server.attributes.uuid).queryKey, {
                object: 'list',
                data: [],
            } as never);
        },
    });

    expect(leaf.status).toBe('success');
    expect(requests).toEqual([]);
});

it('keeps non-administrators out of the admin area before any admin request', { timeout: 20_000 }, async () => {
    const { RouteAccessDenied } = await import('@/router/RouteError');
    const { router, requests } = await loadRoute('/panel/nodes');
    const panel = router.state.matches.find((match) => match.routeId === '/authenticated/panel');

    expect(panel?.status).toBe('error');
    expect(panel?.error).toBeInstanceOf(RouteAccessDenied);
    expect(panel?.error).toHaveProperty('message', expect.stringContaining('administrative area'));
    expect(requests).toEqual([]);
});

it('parses numeric route ids and treats anything else as not found', { timeout: 20_000 }, async () => {
    const { router, requests } = await loadRoute('/panel/nodes/abc/settings', { rootAdmin: true });

    expect(router.state.matches.some((match) => match.status === 'notFound')).toBe(true);
    expect(requests).toEqual([]);

    const params = (path: string, routeId: string) =>
        router.matchRoutes(path).find((match) => match.routeId === routeId)?.params;

    expect(params('/panel/nodes/42/settings', '/authenticated/panel/nodes/$id')).toMatchObject({ id: 42 });
    expect(params('/panel/eggs/7/variables', '/authenticated/panel/eggs/$eggId')).toMatchObject({ eggId: 7 });
    expect(params('/server/abc/schedules/3', '/authenticated/server/$id/schedules/$scheduleId')).toMatchObject({
        id: 'abc',
        scheduleId: 3,
    });
    expect(router.buildLocation({ to: '/panel/nodes/$id/settings', params: { id: 42 } }).pathname).toBe(
        '/panel/nodes/42/settings'
    );
});
