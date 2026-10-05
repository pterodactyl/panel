import { QueryClient } from '@tanstack/react-query';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { invalidateGeneratedOperations } from '@/api/mutationUtils';
import { MAX_PER_PAGE } from '@/api/pagination';
import { adminEggsQueryOptions } from '@/api/admin/eggs/queries';
import { allAdminLocationsQueryOptions } from '@/api/admin/locations/queries';
import { allAdminNodesQueryOptions } from '@/api/admin/nodes/queries';
import {
    adminServerBuildAllocationsQueryOptions,
    adminServerUnassignedAllocationsQueryOptions,
} from '@/api/admin/servers/queries';

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;

const requests = () => adapter.mock.calls.map(([config]) => new URL(config.url!, 'https://panel.test'));
const completePage = { total: 3, count: 3, per_page: MAX_PER_PAGE, current_page: 1, total_pages: 1 };

describe('complete admin lists', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
        adapter.mockImplementation(async (config) => {
            const current = Number(new URL(config.url!, 'https://panel.test').searchParams.get('page'));

            return {
                config,
                headers: {},
                status: 200,
                statusText: 'OK',
                data: {
                    object: 'list',
                    data: [{ object: 'item', attributes: { id: current } }],
                    meta: {
                        pagination: {
                            total: 3,
                            count: 1,
                            per_page: MAX_PER_PAGE,
                            current_page: current,
                            total_pages: 3,
                        },
                    },
                },
            };
        });
    });

    afterEach(() => {
        client.clear();
        http.defaults.adapter = originalAdapter;
    });

    const expectAllPages = (path: string) => {
        expect(requests().map((url) => [url.pathname, url.searchParams.get('page')])).toEqual([
            [path, '1'],
            [path, '2'],
            [path, '3'],
        ]);
        expect(requests().every((url) => url.searchParams.get('per_page') === String(MAX_PER_PAGE))).toBe(true);
        expect(requests().every((url) => url.searchParams.get('sort') === 'id')).toBe(true);
    };

    it('pages through all eggs within the page size limit', async () => {
        const options = adminEggsQueryOptions();

        const result = await client.fetchQuery(options);

        expect(result.data.map((egg) => egg.attributes.id)).toEqual([1, 2, 3]);
        expect(result.meta.pagination).toEqual(completePage);
        expectAllPages('/api/admin/eggs');
        await invalidateGeneratedOperations(client, ['adminListEggs']);
        expect(client.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
    });

    it('pages through all nodes with their location', async () => {
        const options = allAdminNodesQueryOptions();

        const result = await client.fetchQuery(options);

        expect(result.data.map((node) => node.attributes.id)).toEqual([1, 2, 3]);
        expect(result.meta.pagination).toEqual(completePage);
        expectAllPages('/api/admin/nodes');
        expect(requests().map((url) => url.searchParams.get('include'))).toEqual(['location', 'location', 'location']);
        await invalidateGeneratedOperations(client, ['adminListNodes']);
        expect(client.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
    });

    it('pages through all locations', async () => {
        const options = allAdminLocationsQueryOptions();

        const result = await client.fetchQuery(options);

        expect(result.data.map((location) => location.attributes.id)).toEqual([1, 2, 3]);
        expect(result.meta.pagination).toEqual(completePage);
        expectAllPages('/api/admin/locations');
        await invalidateGeneratedOperations(client, ['adminListLocations']);
        expect(client.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
    });

    it.each([
        ['assigned', () => adminServerBuildAllocationsQueryOptions(7, 4), '7'],
        ['unassigned', () => adminServerUnassignedAllocationsQueryOptions(4), ''],
    ] as const)('pages through all %s allocations of a node', async (_name, queryOptions, serverFilter) => {
        const options = queryOptions();

        const result = await client.fetchQuery(options);

        expect(result.data.map((allocation) => allocation.attributes.id)).toEqual([1, 2, 3]);
        expect(result.meta.pagination).toEqual(completePage);
        expectAllPages('/api/admin/nodes/4/allocations');
        expect(requests().map((url) => url.searchParams.get('filter[server_id]'))).toEqual(
            Array.from({ length: 3 }, () => serverFilter)
        );
        await invalidateGeneratedOperations(client, ['adminListNodeAllocations']);
        expect(client.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
    });
});
