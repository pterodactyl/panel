/** @vitest-environment jsdom */
import { act, cleanup, render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { afterEach, beforeAll, beforeEach, expect, it, vi } from 'vitest';
import type { ScreenOptions, SiteExtensionEntry } from '@/extensions/registry';
import type { SdkServer } from '@/sdk';

const user = {
    uuid: 'test-user',
    username: 'alex',
    email: 'alex@example.test',
    root_admin: false,
    use_totp: false,
    language: 'en',
    updated_at: '2026-01-01T00:00:00Z',
    created_at: '2026-01-01T00:00:00Z',
};

// Transforms the SDK module graph once so the per-test imports after resetModules stay fast.
beforeAll(async () => {
    await import('@/sdk/testing');
}, 30_000);

beforeEach(() => {
    vi.resetModules();
    vi.spyOn(console, 'error').mockImplementation(() => {});
    (window as Window & { PterodactylUser?: unknown }).PterodactylUser = user;
});
afterEach(() => {
    cleanup();
    delete (window as Window & { PterodactylUser?: unknown }).PterodactylUser;
});

async function mountPanel(entries: SiteExtensionEntry[], options: { server?: SdkServer; path?: string } = {}) {
    const registry = await import('@/extensions/registry');
    const { ScreenGate } = await import('@/extensions/screenNavigation');
    const { default: NavigationLabel } = await import('@/extensions/NavigationLabel');
    const { extensionScreenComponent } = await import('@/extensions/screen');
    const { serverQueryKey } = await import('@/api/server/queries');
    registry.prepareExtensions(entries);
    const area = entries[0]?.screens?.[0]?.area ?? 'server';
    const screens = registry.getExtensionScreens(area);

    const client = new QueryClient({ defaultOptions: { queries: { staleTime: Infinity, retry: false } } });
    if (options.server) client.setQueryData(serverQueryKey('abc'), options.server);

    function Layout() {
        return (
            <>
                <nav>
                    <a href={'/'}>Core entry</a>
                    {screens.map((registration) => (
                        <ScreenGate key={`${registration.extensionId}:${registration.id}`} screen={registration}>
                            <a href={`/${registration.path}`}>
                                <NavigationLabel {...registration.nav!} screen={registration} />
                            </a>
                        </ScreenGate>
                    ))}
                </nav>
                <Outlet />
            </>
        );
    }
    const root = createRootRoute({ component: Outlet });
    const authenticated = createRoute({ getParentRoute: () => root, id: 'authenticated', component: Outlet });
    const layout = createRoute({ getParentRoute: () => authenticated, path: 'server/$id', component: Layout });
    const index = createRoute({ getParentRoute: () => layout, path: '/', component: () => <p>index</p> });
    const router = createRouter({
        routeTree: root.addChildren([
            authenticated.addChildren([
                layout.addChildren([
                    index,
                    ...screens.map((registration) =>
                        createRoute({
                            getParentRoute: () => layout,
                            path: registration.path,
                            component: extensionScreenComponent(registration, area),
                        })
                    ),
                ]),
            ]),
        ]),
        history: createMemoryHistory({ initialEntries: [options.path ?? '/server/abc'] }),
    });
    render(
        <QueryClientProvider client={client}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    await screen.findByText('Core entry');

    return {
        registry,
        router,
        load(extensionId: string, options: Record<string, ScreenOptions> = {}) {
            const batch = registry.createExtensionRegistryBatch();
            for (const registration of screens.filter((item) => item.extensionId === extensionId)) {
                registry.registerScreen(
                    extensionId,
                    registration.id,
                    async () => ({ default: () => <p>{registration.id} screen</p> }),
                    batch,
                    options[registration.id]
                );
            }
            act(() => registry.commitExtensionRegistryBatch(extensionId, batch));
        },
    };
}

it('lists and routes an egg-gated screen only for servers whose egg matches', async () => {
    const { createTestServer } = await import('@/sdk/testing');
    const entries: SiteExtensionEntry[] = [
        {
            id: 'mods',
            entry: '/mods.js',
            screens: [
                {
                    id: 'mods',
                    area: 'server',
                    path: 'mods',
                    nav: { label: 'Mods' },
                    when: { eggTags: { any: ['Minecraft'] } },
                },
            ],
        },
    ];

    const matching = await mountPanel(entries, {
        server: createTestServer({ eggTags: ['minecraft', 'paper'] }),
        path: '/server/abc/mods',
    });
    expect(screen.getByText('Mods')).toBeVisible();
    matching.load('mods');
    expect(await screen.findByText('mods screen')).toBeVisible();
    cleanup();

    vi.resetModules();
    await mountPanel(entries, { server: createTestServer({ eggTags: ['palworld'] }), path: '/server/abc/mods' });
    expect(screen.queryByText('Mods')).not.toBeInTheDocument();
    expect(await screen.findByText('404')).toBeVisible();
    expect(screen.getByText('Core entry')).toBeVisible();
});

it('holds a predicate-gated entry back until its bundle answers, then keeps it reactive', async () => {
    const { createTestServer } = await import('@/sdk/testing');
    const seen: unknown[] = [];
    let allowed = false;
    const panel = await mountPanel(
        [
            {
                id: 'domains',
                entry: '/domains.js',
                config: { zones: 2 },
                screens: [
                    {
                        id: 'domains',
                        area: 'server',
                        path: 'domains',
                        nav: { label: 'Subdomains' },
                        when: { runtime: true },
                    },
                    { id: 'plain', area: 'server', path: 'plain', nav: { label: 'Plain' } },
                ],
            },
        ],
        { server: createTestServer({ name: 'Lobby' }) }
    );
    expect(screen.getByText('Plain')).toBeVisible();
    expect(screen.queryByText('Subdomains')).not.toBeInTheDocument();

    panel.load('domains', {
        domains: {
            visible: (context) => {
                seen.push(context);
                return allowed;
            },
        },
    });
    expect(screen.queryByText('Subdomains')).not.toBeInTheDocument();
    expect(seen.at(-1)).toMatchObject({
        user: { username: 'alex' },
        server: { attributes: { name: 'Lobby' } },
        config: { zones: 2 },
    });

    await act(async () => {
        await panel.router.navigate({ to: '/server/abc/domains' } as never);
    });
    expect(await screen.findByText('404')).toBeVisible();

    allowed = true;
    await act(async () => {
        await panel.router.navigate({ to: '/server/abc' } as never);
    });
    expect(await screen.findByText('Subdomains')).toBeVisible();
    await act(async () => {
        await panel.router.navigate({ to: '/server/abc/domains' } as never);
    });
    expect(await screen.findByText('domains screen')).toBeVisible();
});

it('lets a predicate use hooks and suspend without holding up navigation', async () => {
    const { useSuspenseQuery } = await import('@tanstack/react-query');
    let release!: (quota: number) => void;
    const quota = new Promise<number>((resolve) => {
        release = resolve;
    });
    const panel = await mountPanel([
        {
            id: 'creator',
            entry: '/creator.js',
            screens: [
                {
                    id: 'create',
                    area: 'account',
                    path: 'create',
                    nav: { label: 'Create server' },
                    when: { runtime: true },
                },
            ],
        },
    ]);
    const useHasQuota = () => useSuspenseQuery({ queryKey: ['quota'], queryFn: () => quota }).data > 0;
    panel.load('creator', { create: { visible: useHasQuota } });
    expect(screen.getByText('Core entry')).toBeVisible();
    expect(screen.queryByText('Create server')).not.toBeInTheDocument();
    await act(async () => release(3));
    expect(await screen.findByText('Create server')).toBeVisible();
});

it('hides an entry whose predicate throws and reports it against the extension', async () => {
    const panel = await mountPanel([
        {
            id: 'broken',
            entry: '/broken.js',
            screens: [
                { id: 'gated', area: 'account', path: 'gated', nav: { label: 'Gated' }, when: { runtime: true } },
                { id: 'open', area: 'account', path: 'open', nav: { label: 'Open' } },
            ],
        },
    ]);
    panel.load('broken', {
        gated: {
            visible: () => {
                throw new Error('predicate exploded');
            },
        },
    });
    await waitFor(() =>
        expect(panel.registry.getExtensionStates().find((state) => state.id === 'broken')?.error).toMatch(
            /screen "gated" visibility.*predicate exploded/
        )
    );
    expect(screen.queryByText('Gated')).not.toBeInTheDocument();
    expect(screen.getByText('Open')).toBeVisible();
    expect(screen.getByText('Core entry')).toBeVisible();
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();

    await act(async () => {
        await panel.router.navigate({ to: '/server/abc/gated' } as never);
    });
    expect(await screen.findByText('404')).toBeVisible();
});

it('keeps a predicate-gated entry hidden when its bundle fails to load', async () => {
    const panel = await mountPanel([
        {
            id: 'offline',
            entry: '/offline.js',
            screens: [{ id: 'main', area: 'account', path: 'main', nav: { label: 'Main' }, when: { runtime: true } }],
        },
    ]);
    act(() => panel.registry.failExtensionLoad('offline', 'boot', new Error('network')));
    expect(screen.queryByText('Main')).not.toBeInTheDocument();
});

it('shows the manifest badge until the extension supplies a live one', async () => {
    let open: number | null | undefined = 4;
    const panel = await mountPanel([
        {
            id: 'tickets',
            entry: '/tickets.js',
            screens: [
                { id: 'tickets', area: 'account', path: 'tickets', nav: { label: 'Tickets', badge: 'New' } },
                { id: 'archive', area: 'account', path: 'archive', nav: { label: 'Archive', badge: 'Beta' } },
            ],
        },
    ]);
    expect(screen.getByText('New')).toBeVisible();

    panel.load('tickets', { tickets: { badge: () => open } });
    expect(await screen.findByText('4')).toBeVisible();
    expect(screen.queryByText('New')).not.toBeInTheDocument();
    expect(screen.getByText('Beta')).toBeVisible();

    open = null;
    await act(async () => {
        await panel.router.navigate({ to: '/server/abc/archive' } as never);
    });
    await waitFor(() => expect(screen.queryByText('4')).not.toBeInTheDocument());
    expect(screen.queryByText('New')).not.toBeInTheDocument();

    open = undefined;
    await act(async () => {
        await panel.router.navigate({ to: '/server/abc' } as never);
    });
    expect(await screen.findByText('New')).toBeVisible();
});

it('falls back to the manifest badge while a live badge is slow or failing', async () => {
    const { useSuspenseQuery } = await import('@tanstack/react-query');
    let release!: (used: string) => void;
    const usage = new Promise<string>((resolve) => {
        release = resolve;
    });
    const panel = await mountPanel([
        {
            id: 'badges',
            entry: '/badges.js',
            screens: [
                { id: 'slow', area: 'account', path: 'slow', nav: { label: 'Slow', badge: 'Loading' } },
                { id: 'failing', area: 'account', path: 'failing', nav: { label: 'Failing', badge: 'Static' } },
            ],
        },
    ]);
    const useUsage = () => useSuspenseQuery({ queryKey: ['usage'], queryFn: () => usage }).data;
    panel.load('badges', {
        slow: { badge: useUsage },
        failing: {
            badge: () => {
                throw new Error('badge exploded');
            },
        },
    });
    expect(screen.getByText('Slow')).toBeVisible();
    expect(screen.getByText('Loading')).toBeVisible();
    expect(await screen.findByText('Static')).toBeVisible();
    await waitFor(() =>
        expect(panel.registry.getExtensionStates().find((state) => state.id === 'badges')?.error).toMatch(
            /screen "failing" badge.*badge exploded/
        )
    );
    expect(screen.queryByRole('alert')).not.toBeInTheDocument();

    await act(async () => release('2/5'));
    expect(await screen.findByText('2/5')).toBeVisible();
    expect(screen.queryByText('Loading')).not.toBeInTheDocument();
});
