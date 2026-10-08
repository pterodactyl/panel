import { QueryClient } from '@tanstack/react-query';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { allAdminTagsQueryOptions, adminTagsQueryOptions, type AdminTag } from './queries';
import { invalidateGeneratedOperations } from '@/api/mutationUtils';

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;

const tag = (id: number): AdminTag => ({
    object: 'tag',
    attributes: {
        id,
        name: `Tag ${id}`,
        slug: `tag-${id}`,
        color: null,
        is_predefined: false,
        legacy_nest_id: null,
        eggs_count: 0,
        nodes_count: 0,
        created_at: '',
        updated_at: '',
    },
});

const page = (current: number, total: number, data: AdminTag[] = [tag(current)]) => ({
    object: 'list',
    data,
    meta: { pagination: { current_page: current, total_pages: total, per_page: 100, count: data.length, total } },
});

describe('complete tag collection', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    });

    afterEach(() => {
        client.clear();
        http.defaults.adapter = originalAdapter;
        vi.useRealTimers();
    });

    it.each([0, 1, 3])('loads a collection with %i pages without sharing the paginated cache', async (total) => {
        adapter.mockImplementation(async (config) => {
            const current = Number(new URL(config.url!, 'https://panel.test').searchParams.get('page'));

            return {
                config,
                headers: {},
                status: 200,
                statusText: 'OK',
                data: page(current, total, total ? [tag(current)] : []),
            };
        });
        const options = allAdminTagsQueryOptions();
        const listOptions = adminTagsQueryOptions();
        const firstPage = page(1, total);

        client.setQueryData(listOptions.queryKey, firstPage);

        const result = await client.fetchQuery(options);

        expect(result).toEqual(Array.from({ length: total }, (_, i) => tag(i + 1)));
        expect(adapter).toHaveBeenCalledTimes(Math.max(total, 1));
        expect(client.getQueryData(listOptions.queryKey)).toEqual(firstPage);
        await invalidateGeneratedOperations(client, ['adminListTags']);
        expect(client.getQueryState(options.queryKey)?.isInvalidated).toBe(true);
        expect(client.getQueryState(listOptions.queryKey)?.isInvalidated).toBe(true);
    });

    it('stops after bounded retries on page two and can explicitly retry', async () => {
        vi.useFakeTimers();
        let fail = true;

        adapter.mockImplementation(async (config) => {
            const current = Number(new URL(config.url!, 'https://panel.test').searchParams.get('page'));

            if (current === 2 && fail) {
                throw new Error('offline');
            }

            return { config, headers: {}, status: 200, statusText: 'OK', data: page(current, 2) };
        });
        const options = { ...allAdminTagsQueryOptions(), retry: 1, retryDelay: 10 };
        const failed = expect(client.fetchQuery(options)).rejects.toThrow('offline');

        await vi.advanceTimersByTimeAsync(100);
        await failed;
        expect(adapter).toHaveBeenCalledTimes(4);
        expect(client.getQueryData(options.queryKey)).toBeUndefined();
        await vi.advanceTimersByTimeAsync(60000);
        expect(adapter).toHaveBeenCalledTimes(4);

        fail = false;
        await expect(client.fetchQuery(options)).resolves.toEqual([tag(1), tag(2)]);
    });

    it('does not start another page after cancellation', async () => {
        const options = allAdminTagsQueryOptions();

        adapter.mockImplementation(async (config) => {
            await client.cancelQueries({ queryKey: options.queryKey });
            expect(config.signal?.aborted).toBe(true);

            return { config, headers: {}, status: 200, statusText: 'OK', data: page(1, 2) };
        });

        await expect(client.fetchQuery(options)).rejects.toThrow();
        expect(adapter).toHaveBeenCalledOnce();
        expect(client.getQueryData(options.queryKey)).toBeUndefined();
    });

    it('rejects a repeated page instead of looping', async () => {
        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: page(1, 2),
        }));
        await expect(client.fetchQuery(allAdminTagsQueryOptions())).rejects.toThrow('pagination');
        expect(adapter).toHaveBeenCalledTimes(2);
    });
});
