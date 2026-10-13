/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, within } from '@testing-library/react';
import { QueryClientProvider } from '@tanstack/react-query';
import {
    createMemoryHistory,
    createRootRoute,
    createRoute,
    createRouter,
    Outlet,
    RouterProvider,
} from '@tanstack/react-router';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { ActivityLogFilters } from '@/api/activity';
import { queryClient } from '@/api/queryClient';
import { serverQueryOptions } from '@/api/server/queries';
import { parseActivitySearch } from '@/router/search';
import { createTestServer, renderView } from '@/sdk/testing';
import { getViewDefinition } from '../registry';
import { View } from '../View';
import { eventGroups } from '.';
import type { ActivityLogModel } from './types';

const mocks = vi.hoisted(() => {
    const logs = vi.fn();
    const facets = vi.fn();

    return {
        logs,
        facets,
        logOptions: (scope: string, filters: ActivityLogFilters) => ({
            queryKey: [scope, 'logs', filters],
            queryFn: () => logs(scope, filters),
        }),
        facetOptions: (scope: string) => ({ queryKey: [scope, 'facets'], queryFn: () => facets(scope) }),
    };
});

vi.mock('@/api/account/activity/queries', () => ({
    accountActivityLogsQueryOptions: (filters: ActivityLogFilters) => mocks.logOptions('account', filters),
    accountActivityFacetsQueryOptions: () => mocks.facetOptions('account'),
}));
vi.mock('@/api/server/activity/queries', () => ({
    serverActivityLogsQueryOptions: (_uuid: string, filters: ActivityLogFilters) => mocks.logOptions('server', filters),
    serverActivityFacetsQueryOptions: () => mocks.facetOptions('server'),
}));
vi.mock('@/api/admin/activity/queries', () => ({
    adminActivityLogsQueryOptions: (filters: ActivityLogFilters) => mocks.logOptions('admin', filters),
    adminActivityFacetsQueryOptions: () => mocks.facetOptions('admin'),
}));

const activity = (id: string, event: string) => ({
    object: 'activity_log',
    attributes: {
        id,
        batch: null,
        event,
        is_api: false,
        ip: '203.0.113.10',
        description: null,
        properties: { useragent: 'Firefox' },
        has_additional_metadata: false,
        timestamp: '2026-10-12T09:30:00Z',
        relationships: { actor: { object: 'user', attributes: { username: 'alex', email: 'alex@example.test' } } },
    },
});

let router: ReturnType<typeof createActivityRouter> | undefined;

function createActivityRouter(path: string) {
    const root = createRootRoute({ component: Outlet });
    const authenticated = createRoute({ getParentRoute: () => root, id: 'authenticated', component: Outlet });
    const server = createRoute({ getParentRoute: () => authenticated, path: 'server/$id', component: Outlet });
    const account = createRoute({
        getParentRoute: () => authenticated,
        path: 'account/activity',
        validateSearch: parseActivitySearch,
        component: () => <View name='activity.log' scope='account' />,
    });
    const admin = createRoute({
        getParentRoute: () => authenticated,
        path: 'panel/activity',
        validateSearch: parseActivitySearch,
        component: () => <View name='activity.log' scope='admin' />,
    });
    const serverActivity = createRoute({
        getParentRoute: () => server,
        path: 'activity',
        validateSearch: parseActivitySearch,
        component: () => <View name='activity.log' scope='server' />,
    });

    return createRouter({
        routeTree: root.addChildren([
            authenticated.addChildren([account, admin, server.addChildren([serverActivity])]),
        ]),
        history: createMemoryHistory({ initialEntries: [path] }),
    });
}

async function renderLog(path: string) {
    queryClient.setQueryData(serverQueryOptions('abc').queryKey, createTestServer({ identifier: 'abc', uuid: 'uuid' }));
    router = createActivityRouter(path);

    await act(async () => {
        render(
            <QueryClientProvider client={queryClient}>
                <RouterProvider router={router!} />
            </QueryClientProvider>
        );
    });
}

beforeEach(() => {
    vi.clearAllMocks();
    mocks.logs.mockResolvedValue({
        object: 'list',
        data: [activity('1', 'server:power.start')],
        meta: { pagination: { total: 50, count: 25, per_page: 25, current_page: 1, total_pages: 2 } },
    });
    mocks.facets.mockResolvedValue({
        data: { events: ['server:power.start'], users: [{ id: 7, username: 'sam', email: 'sam@example.test' }] },
    });
});

afterEach(() => {
    cleanup();
    queryClient.clear();
    router = undefined;
});

describe('activity views from fixtures', () => {
    it('renders entries with their actor, event link and description values', () => {
        const { container, unmount } = renderView('activity.log', { fixture: 'account' });

        expect(within(container).getAllByText('alex')).toHaveLength(3);
        expect(within(container).getByRole('link', { name: 'user:api-key.create' })).toHaveAttribute(
            'href',
            '/account/activity?event=user%3Aapi-key.create'
        );
        expect(within(container).getByRole('button', { name: 'View additional event metadata' })).toBeVisible();
        expect(container).toHaveTextContent('System');
        expect(within(container).getAllByRole('combobox')).toHaveLength(1);
        unmount();
    });

    it('offers to clear filters that match nothing, and filters by user on a server', () => {
        const { container, unmount } = renderView('activity.log', { fixture: 'filtered' });

        expect(container).toHaveTextContent('No matching activity');
        expect(within(container).getAllByRole('combobox')).toHaveLength(2);
        unmount();
    });

    it.each(['page.account.activity', 'page.server.activity'] as const)('composes %s from the log', (name) => {
        const { container, unmount } = renderView(name);

        expect(container).toHaveTextContent('Showing');
        unmount();
    });

    it('asks for the next page and clears filters through the model', () => {
        const fixture = getViewDefinition('activity.log')!.fixtures!.filtered!() as ActivityLogModel;
        const actions = { setEvent: vi.fn(), setUser: vi.fn(), clearFilters: vi.fn(), setPage: vi.fn() };
        const { container, unmount } = renderView('activity.log', {
            model: {
                ...fixture,
                pagination: { total: 50, count: 25, perPage: 25, currentPage: 1, totalPages: 2 },
                actions,
            },
        });
        const view = within(container);

        fireEvent.click(view.getByRole('button', { name: '2' }));
        fireEvent.click(view.getByRole('button', { name: /Clear Filters/ }));
        fireEvent.click(view.getByRole('button', { name: 'Clear filters' }));

        expect(actions.setPage).toHaveBeenCalledWith(2);
        expect(actions.clearFilters).toHaveBeenCalledTimes(2);
        unmount();
    });

    it('groups events by resource with readable labels', () => {
        expect(
            eventGroups(['user:api-key.delete', 'user:api-key.create', 'server:sftp.write', 'auth:success'])
        ).toEqual([
            {
                label: 'API key',
                options: [
                    { value: 'user:api-key.create', label: 'Create', textLabel: 'API key · Create' },
                    { value: 'user:api-key.delete', label: 'Delete', textLabel: 'API key · Delete' },
                ],
            },
            { label: 'Auth', options: [{ value: 'auth:success', label: 'Success', textLabel: 'Auth · Success' }] },
            { label: 'SFTP', options: [{ value: 'server:sftp.write', label: 'Write', textLabel: 'SFTP · Write' }] },
        ]);
    });
});

describe('activity.log', () => {
    it('loads the account log without the user filter', async () => {
        await renderLog('/account/activity');

        expect(await screen.findByRole('link', { name: 'server:power.start' })).toHaveAttribute(
            'href',
            '/account/activity?event=server%3Apower.start'
        );
        expect(mocks.logs).toHaveBeenCalledWith('account', {
            page: 1,
            filters: { event: undefined, user: undefined },
            sorts: { timestamp: -1 },
        });
        expect(mocks.facets).toHaveBeenCalledWith('account');
        expect(mocks.facets).not.toHaveBeenCalledWith('server');
        expect(screen.getAllByRole('combobox')).toHaveLength(1);
    });

    it('keeps both filters when changing pages of a server log', async () => {
        await renderLog('/server/abc/activity?event=server%3Apower.start&user=7');

        expect(await screen.findByRole('link', { name: 'server:power.start' })).toHaveAttribute(
            'href',
            '/server/abc/activity?event=server%3Apower.start'
        );
        expect(mocks.logs).toHaveBeenCalledWith('server', {
            page: 1,
            filters: { event: 'server:power.start', user: '7' },
            sorts: { timestamp: -1 },
        });
        expect(screen.getAllByRole('combobox')).toHaveLength(2);

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: '2' }));
        });

        expect(router!.state.location.pathname).toBe('/server/abc/activity');
        expect(router!.state.location.search).toEqual({ page: 2, event: 'server:power.start', user: '7' });

        await act(async () => {
            fireEvent.click(screen.getByRole('button', { name: /Clear Filters/ }));
        });

        expect(router!.state.location.search).toEqual({});
    });

    it('loads the admin log with its user filter', async () => {
        await renderLog('/panel/activity?event=server%3Apower.start');

        expect(await screen.findByRole('link', { name: 'server:power.start' })).toHaveAttribute(
            'href',
            '/panel/activity?event=server%3Apower.start'
        );
        expect(mocks.facets).toHaveBeenCalledWith('admin');
        expect(screen.getAllByRole('combobox')).toHaveLength(2);
    });
});
