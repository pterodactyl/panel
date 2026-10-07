/** @vitest-environment jsdom */
import { cleanup, fireEvent, render, screen } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { AxiosError, type AxiosResponse } from 'axios';
import { afterEach, expect, it, vi } from 'vitest';
import { hasBootstrapSession } from '@/bootstrap';
import type * as ScreenBlock from '@/components/elements/ScreenBlock';
import RouteError, { RouteAccessDenied } from './RouteError';

vi.mock('@/components/elements/ScreenBlock', async (original) => ({
    ...(await original<typeof ScreenBlock>()),
    ServerError: ({ message, onRetry }: { message: string; onRetry?: () => void }) => (
        <button onClick={onRetry}>{message}</button>
    ),
}));

type BootstrapWindow = Window & { PterodactylUser?: unknown };

afterEach(() => {
    cleanup();
    delete (window as BootstrapWindow).PterodactylUser;
});

const httpError = (status: number) =>
    new AxiosError('Request failed', undefined, undefined, undefined, {
        status,
        data: { errors: [{ detail: `failed with ${status}` }] },
    } as AxiosResponse);

function mount(loader: () => unknown) {
    const root = createRootRoute({ component: Outlet });
    const router = createRouter({
        routeTree: root.addChildren([
            createRoute({ getParentRoute: () => root, path: '/page', loader, component: () => <p>page loaded</p> }),
            createRoute({ getParentRoute: () => root, path: '/auth/login', component: () => <p>sign in</p> }),
        ]),
        history: createMemoryHistory({ initialEntries: ['/page'] }),
        defaultErrorComponent: RouteError,
    });
    render(
        <QueryClientProvider client={new QueryClient()}>
            <RouterProvider router={router} />
        </QueryClientProvider>
    );

    return router;
}

it('continues at sign in and ends the session when a loader is unauthenticated', async () => {
    (window as BootstrapWindow).PterodactylUser = { uuid: 'user', created_at: '', updated_at: '' };
    expect(hasBootstrapSession()).toBe(true);
    const router = mount(() => {
        throw httpError(401);
    });

    expect(await screen.findByText('sign in')).toBeVisible();
    expect(router.state.location.pathname).toBe('/auth/login');
    expect(router.state.location.search).toEqual({ redirect: '/page' });
    expect(hasBootstrapSession()).toBe(false);
});

it('shows the access screen for forbidden responses and route guards', async () => {
    mount(() => {
        throw httpError(403);
    });
    expect(await screen.findByText('Access Denied')).toBeVisible();
    cleanup();

    mount(() => {
        throw new RouteAccessDenied('Administrators only.');
    });
    expect(await screen.findByText('Administrators only.')).toBeVisible();
});

it('shows the not found screen for missing resources', async () => {
    mount(() => {
        throw httpError(404);
    });

    expect(await screen.findByText('The requested resource was not found.')).toBeVisible();
});

it('retries other failures by reloading the route', async () => {
    const loader = vi.fn().mockRejectedValueOnce(httpError(500)).mockResolvedValue(null);
    mount(loader);

    fireEvent.click(await screen.findByRole('button', { name: 'failed with 500' }));

    expect(await screen.findByText('page loaded')).toBeVisible();
    expect(loader).toHaveBeenCalledTimes(2);
});
