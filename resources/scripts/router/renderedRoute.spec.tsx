/** @vitest-environment jsdom */
import { act, cleanup, render, screen } from '@testing-library/react';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { afterEach, expect, it } from 'vitest';
import { useIsRenderedRoute } from './renderedRoute';

afterEach(cleanup);

function Layout() {
    const isConsole = useIsRenderedRoute('/server/abc');

    return (
        <>
            <output data-testid='console'>{isConsole ? 'shown' : 'hidden'}</output>
            <Outlet />
        </>
    );
}

function mount(initialPath: string) {
    let finishLoading!: () => void;
    const loading = new Promise<void>((resolve) => {
        finishLoading = resolve;
    });

    const rootRoute = createRootRoute();
    const serverRoute = createRoute({ getParentRoute: () => rootRoute, path: '/server/$id', component: Layout });
    const router = createRouter({
        routeTree: rootRoute.addChildren([
            serverRoute.addChildren([
                createRoute({ getParentRoute: () => serverRoute, path: '/', component: () => null }),
                createRoute({
                    getParentRoute: () => serverRoute,
                    path: 'files',
                    loader: () => loading,
                    component: () => <p>Files</p>,
                }),
            ]),
        ]),
        history: createMemoryHistory({ initialEntries: [initialPath] }),
        defaultNotFoundComponent: () => <p>Not Found</p>,
        // The page being left stays on screen while the next one loads, as in the panel.
        defaultPendingMs: 60_000,
    });

    render(<RouterProvider router={router} />);

    return { router, finishLoading };
}

it('follows the page the outlet renders rather than the location while a page loads', async () => {
    const { router, finishLoading } = mount('/server/abc');

    expect(await screen.findByText('shown')).toBeVisible();

    // Pushed through the history: `navigate` is typed against the panel's own routes.
    await act(async () => router.history.push('/server/abc/files'));

    // The location has already moved on, but the outlet has not swapped yet.
    expect(router.state.location.pathname).toBe('/server/abc/files');
    expect(screen.queryByText('Files')).toBeNull();
    expect(screen.getByTestId('console')).toHaveTextContent('shown');

    await act(async () => finishLoading());

    expect(await screen.findByText('Files')).toBeVisible();
    expect(screen.getByTestId('console')).toHaveTextContent('hidden');
});

it('ignores a trailing slash on either side', async () => {
    mount('/server/abc/');

    expect(await screen.findByText('shown')).toBeVisible();
});

it('is not rendered when the location matches no route', async () => {
    mount('/server/abc/doesnotexist');

    expect(await screen.findByText('Not Found')).toBeVisible();
    expect(screen.getByTestId('console')).toHaveTextContent('hidden');
});
