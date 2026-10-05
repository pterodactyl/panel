// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, renderHook, waitFor } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { invalidateGeneratedOperations } from '@/api/mutationUtils';
import { allAdminUsersQueryOptions, adminUsersQueryOptions, useAllAdminUsers } from '@/api/admin/users/queries';
import { allAdminLocationsQueryOptions, useAllAdminLocations } from '@/api/admin/locations/queries';
import { allAdminDatabaseHostsQueryOptions, useAllAdminDatabaseHosts } from '@/api/admin/database-hosts/queries';
import { adminEggsQueryOptions } from '@/api/admin/eggs/queries';
import { useAdminNodesGroupedByLocation } from '@/api/admin/nodes/queries';
import { useAdminServerUnassignedAllocations } from '@/api/admin/servers/queries';

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);

const item = (id: number, attributes: Record<string, string | number> = {}) => ({
    object: 'item',
    attributes: { id, ...attributes },
});

const serve = (
    totals: Record<string, number>,
    attributes: (id: number) => Record<string, string | number> = () => ({})
) => {
    const requests: string[] = [];
    adapter.mockImplementation(async (config) => {
        const url = new URL(config.url!, 'https://panel.test');
        const page = Number(url.searchParams.get('page') ?? '1');
        const total = totals[url.pathname] ?? 0;
        const totalPages = Math.max(1, Math.ceil(total / 100));
        const first = (page - 1) * 100 + 1;
        const data = Array.from({ length: Math.max(0, Math.min(100, total - first + 1)) }, (_, index) =>
            item(first + index, attributes(first + index))
        );
        requests.push(`${url.pathname}?page=${page}&per_page=${url.searchParams.get('per_page')}`);

        return {
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: {
                object: 'list',
                data,
                meta: {
                    pagination: {
                        total,
                        count: data.length,
                        per_page: 100,
                        current_page: page,
                        total_pages: totalPages,
                    },
                },
            },
        };
    });

    return requests;
};

describe('complete admin collections', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
    });

    it.each([
        ['users', '/api/admin/users', useAllAdminUsers],
        ['locations', '/api/admin/locations', useAllAdminLocations],
        ['database hosts', '/api/admin/database-hosts', useAllAdminDatabaseHosts],
    ] as const)('loads every page of %s for pickers', async (_name, path, useAll) => {
        const requests = serve({ [path]: 250 });
        const { result } = renderHook(() => useAll(), { wrapper });

        await waitFor(() => expect(result.current.data).toHaveLength(250));
        expect(result.current.data?.map(({ attributes }) => attributes.id)).toEqual(
            Array.from({ length: 250 }, (_, index) => index + 1)
        );
        expect(requests).toEqual([1, 2, 3].map((page) => `${path}?page=${page}&per_page=100`));
    });

    it('keeps the complete collection apart from paged lists and invalidates both together', async () => {
        serve({ '/api/admin/users': 101 });
        const all = await client.fetchQuery(allAdminUsersQueryOptions());
        const firstPage = await client.fetchQuery(adminUsersQueryOptions({ page: 1 }));

        expect(all.data).toHaveLength(101);
        expect(all.meta.pagination).toMatchObject({ count: 101, current_page: 1, total_pages: 1 });
        expect(firstPage.data).toHaveLength(100);

        await invalidateGeneratedOperations(client, ['adminListUsers']);
        expect(client.getQueryState(allAdminUsersQueryOptions().queryKey)?.isInvalidated).toBe(true);
        expect(client.getQueryState(adminUsersQueryOptions({ page: 1 }).queryKey)?.isInvalidated).toBe(true);
    });

    it('loads every egg, location, and database host instead of capping a single page', async () => {
        const requests = serve({ '/api/admin/eggs': 201, '/api/admin/locations': 101, '/api/admin/database-hosts': 1 });

        expect((await client.fetchQuery(adminEggsQueryOptions())).data).toHaveLength(201);
        expect((await client.fetchQuery(allAdminLocationsQueryOptions())).data).toHaveLength(101);
        expect((await client.fetchQuery(allAdminDatabaseHostsQueryOptions())).data).toHaveLength(1);
        expect(requests.every((request) => request.endsWith('per_page=100'))).toBe(true);
    });

    it('groups every node under every location', async () => {
        serve({ '/api/admin/nodes': 150, '/api/admin/locations': 150 }, (id) => ({
            location_id: id,
            name: `node-${id}`,
            fqdn: `node-${id}.test`,
        }));
        const { result } = renderHook(() => useAdminNodesGroupedByLocation(), { wrapper });

        await waitFor(() => expect(result.current.data).toHaveLength(150));
        expect(result.current.data?.at(-1)?.nodes).toEqual([
            { id: 150, locationId: 150, name: 'node-150', fqdn: 'node-150.test' },
        ]);
    });

    it('offers every unassigned allocation of a node', async () => {
        const requests = serve({ '/api/admin/nodes/7/allocations': 120 });
        const { result } = renderHook(() => useAdminServerUnassignedAllocations(7), { wrapper });

        await waitFor(() => expect(result.current.data?.data).toHaveLength(120));
        expect(requests).toEqual([
            '/api/admin/nodes/7/allocations?page=1&per_page=100',
            '/api/admin/nodes/7/allocations?page=2&per_page=100',
        ]);
    });
});
