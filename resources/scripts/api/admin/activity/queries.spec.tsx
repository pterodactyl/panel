// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { cleanup, renderHook } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { afterEach, describe, expect, it } from 'vitest';
import { emptyActivityFacets } from '@/api/activityFacets';
import {
    adminActivityFacetsQueryOptions,
    adminActivityLogsQueryOptions,
    useAdminActivityFacets,
} from '@/api/admin/activity/queries';

const client = new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: Infinity } } });
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);

describe('admin activity queries', () => {
    afterEach(() => {
        cleanup();
        client.clear();
    });

    it('derives facets once per response and keeps them stable across renders', () => {
        client.setQueryData(adminActivityFacetsQueryOptions().queryKey, {
            object: 'activity_filter_options',
            data: { events: ['server:power.start', 7], users: [{ id: 1, username: 'alex' }, { id: 'x' }] },
        } as never);
        const { result, rerender } = renderHook(() => useAdminActivityFacets(), { wrapper });
        const first = result.current;

        rerender();

        expect(result.current).toBe(first);
        expect(first).toEqual({ events: ['server:power.start'], users: [{ id: 1, username: 'alex', email: '' }] });
    });

    it('reports the shared empty facets before the options load', () => {
        client.setQueryDefaults(adminActivityFacetsQueryOptions().queryKey, { enabled: false });
        const { result } = renderHook(() => useAdminActivityFacets(), { wrapper });

        expect(result.current).toBe(emptyActivityFacets);
    });

    it('serializes log filters with the shared activity query builder', () => {
        const [key] = adminActivityLogsQueryOptions({
            page: 2,
            filters: { event: 'server:power.start', user: '4' },
            sorts: { timestamp: -1 },
        }).queryKey;

        expect(key.query).toEqual({
            include: 'actor',
            page: 2,
            'filter[event_name]': 'server:power.start',
            'filter[user_id]': 4,
            sort: '-timestamp',
        });
    });
});
