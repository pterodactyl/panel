/** @vitest-environment jsdom */
import { useEffect } from 'react';
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
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { ExtensionSetupContext } from '@/sdk';
import type { ExtensionModule } from '@/extensions/loader';

beforeEach(() => {
    vi.resetModules();
    vi.spyOn(window, 'scrollTo').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
});
afterEach(() => {
    cleanup();
    vi.useRealTimers();
});

it('renders core navigation before imports settle and recovers resource-specific failures', async () => {
    const registry = await import('@/extensions/registry');
    const { loadExtensions } = await import('@/extensions/loader');
    const { extensionScreenComponent } = await import('@/extensions/screen');

    registry.prepareExtensions([
        { id: 'slow', entry: '/slow.js' },
        { id: 'mod', entry: '/mod.js', screens: [{ id: 'main', area: 'account', path: 'mod/$resource' }] },
        { id: 'collision', entry: '/collision.js', screens: [{ id: 'main', area: 'account', path: 'mod/$other' }] },
    ]);
    let resolveSlow!: (module: ExtensionModule) => void;
    let broken = true;

    function ModScreen() {
        if (broken) {
            throw new Error('resource failed');
        }

        return <p>extension ready</p>;
    }

    const importer = vi.fn((url: string) =>
        url === '/slow.js'
            ? new Promise<ExtensionModule>((resolve) => {
                  resolveSlow = resolve;
              })
            : Promise.resolve({
                  default: {
                      setup: (context: ExtensionSetupContext) =>
                          context.screens.register('main', async () => ({ default: ModScreen })),
                  },
              })
    );

    function Layout() {
        useEffect(() => {
            void loadExtensions(importer);
        }, []);

        return (
            <>
                <button>Core navigation</button>
                <Outlet />
            </>
        );
    }

    const root = createRootRoute({ component: Layout });
    const home = createRoute({ getParentRoute: () => root, path: '/', component: () => <p>core route ready</p> });
    const routes = registry.getExtensionScreens('account').map((registration) =>
        createRoute({
            getParentRoute: () => root,
            path: registration.path,
            component: extensionScreenComponent(registration, 'account'),
        })
    );
    const router = createRouter({
        routeTree: root.addChildren([home, ...routes]),
        history: createMemoryHistory({ initialEntries: ['/'] }),
    });
    const client = new QueryClient();

    render(
        <QueryClientProvider client={client}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    expect(await screen.findByText('core route ready')).toBeVisible();
    expect(registry.getExtensionLoadState('slow')?.status).toBe('loading');
    expect(importer).not.toHaveBeenCalledWith('/collision.js');
    await act(async () => {
        await router.navigate({ to: '/mod/first' } as never);
    });
    expect(await screen.findByRole('button', { name: 'Retry extension' })).toBeVisible();
    expect(screen.getByRole('button', { name: 'Core navigation' })).toBeVisible();
    broken = false;
    await act(async () => {
        await router.navigate({ to: '/mod/second' } as never);
    });
    expect(await screen.findByText('extension ready')).toBeVisible();
    await act(async () => {
        resolveSlow({ default: { setup: () => {} } });
        await loadExtensions(importer);
    });
    expect(importer).toHaveBeenCalledTimes(2);
    client.clear();
});

it('offers page reload for a rejected lazy screen without looping imports', async () => {
    const registry = await import('@/extensions/registry');
    const { extensionScreenComponent } = await import('@/extensions/screen');

    registry.prepareExtensions([
        { id: 'broken-chunk', entry: '/chunk.js', screens: [{ id: 'main', area: 'account', path: 'chunk' }] },
    ]);
    const importer = vi.fn().mockRejectedValue(new Error('chunk unavailable'));
    const batch = registry.createExtensionRegistryBatch();

    registry.registerScreen('broken-chunk', 'main', importer, batch);
    registry.commitExtensionRegistryBatch('broken-chunk', batch);
    const root = createRootRoute({ component: Outlet });
    const registration = registry.getExtensionScreens('account')[0];
    const route = createRoute({
        getParentRoute: () => root,
        path: 'chunk',
        component: extensionScreenComponent(registration, 'account'),
    });
    const router = createRouter({
        routeTree: root.addChildren([route]),
        history: createMemoryHistory({ initialEntries: ['/chunk'] }),
    });

    render(
        <QueryClientProvider client={new QueryClient()}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    expect(await screen.findByRole('button', { name: 'Reload page' })).toBeVisible();
    expect(screen.queryByRole('button', { name: 'Retry extension' })).not.toBeInTheDocument();
    await waitFor(() => expect(importer).toHaveBeenCalledTimes(1));
});

it('passes the current route and its parameters to the screen', async () => {
    const registry = await import('@/extensions/registry');
    const { extensionScreenComponent } = await import('@/extensions/screen');

    registry.prepareExtensions([
        { id: 'params', entry: '/params.js', screens: [{ id: 'main', area: 'account', path: 'probe/$tab' }] },
    ]);
    const batch = registry.createExtensionRegistryBatch();

    registry.registerScreen(
        'params',
        'main',
        async () => ({
            default: ({ data }) => (
                <p>
                    {data.pathname} {data.params.tab}
                </p>
            ),
        }),
        batch
    );
    registry.commitExtensionRegistryBatch('params', batch);
    const root = createRootRoute({ component: Outlet });
    const registration = registry.getExtensionScreens('account')[0];
    const route = createRoute({
        getParentRoute: () => root,
        path: registration.path,
        component: extensionScreenComponent(registration, 'account'),
    });
    const router = createRouter({
        routeTree: root.addChildren([route]),
        history: createMemoryHistory({ initialEntries: ['/probe/some-tab'] }),
    });

    render(
        <QueryClientProvider client={new QueryClient()}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );
    expect(await screen.findByText('/probe/some-tab some-tab')).toBeVisible();
});
