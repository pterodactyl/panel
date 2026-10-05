// @vitest-environment jsdom

import { QueryClient, QueryClientProvider, useQuery } from '@tanstack/react-query';
import { act, cleanup, renderHook, waitFor } from '@testing-library/react';
import type { AxiosError, AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, expectTypeOf, it, vi } from 'vitest';
import http from '@/api/http';
import { queryClient as applicationClient } from '@/api/queryClient';
import {
    serverQueryOptions,
    useCurrentServerPermissions,
    useServerQuery,
    useRenameServer,
    type Server,
} from '@/api/server/queries';
import { adminEggsQueryOptions, useAdminEgg, useAdminEggs } from '@/api/admin/eggs/queries';
import { useServerStartup } from '@/api/server/startup/queries';
import type { ClientGetServerError } from '@/api/generated';

vi.mock('@/router/params', () => ({ useServerRouteId: () => 'server' }));

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);
const server = {
    object: 'server',
    attributes: { uuid: 'server', name: 'Original', internal_id: 1 },
    meta: { is_server_owner: false, user_permissions: ['console.read'] },
} as Server;

describe('native query lifecycle', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: applicationClient.getDefaultOptions() });
        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: server,
        }));
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
        vi.useRealTimers();
    });

    it('shares the loader result across subscribers and revalidates after expiry or invalidation', async () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        const now = Date.now();
        const options = serverQueryOptions('server');
        await client.ensureQueryData({ ...options, revalidateIfStale: true });
        const { result } = renderHook(() => [useServerQuery('server'), useServerQuery('server')], { wrapper });
        await waitFor(() => expect(result.current[0]?.data).toEqual(server));
        await client.ensureQueryData({ ...options, revalidateIfStale: true });
        expect(adapter).toHaveBeenCalledOnce();

        vi.setSystemTime(now + 30_001);
        await act(async () => {
            await client.ensureQueryData({ ...options, revalidateIfStale: true });
        });
        await waitFor(() => expect(adapter).toHaveBeenCalledTimes(2));
        await waitFor(() => expect(client.isFetching()).toBe(0));
        await act(async () => {
            await client.invalidateQueries({ queryKey: options.queryKey });
        });
        expect(adapter).toHaveBeenCalledTimes(3);
    });

    it('uses the same fresh admin list for preload and mount', async () => {
        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: {
                object: 'list',
                data: [],
                meta: { pagination: { total: 0, count: 0, per_page: 100, current_page: 1, total_pages: 1 } },
            },
        }));
        await client.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        const { result } = renderHook(() => useAdminEggs(), { wrapper });
        expect(result.current.data?.data).toEqual([]);
        await act(async () => {
            await client.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        });
        expect(adapter).toHaveBeenCalledOnce();
    });

    it('does not notify permission subscribers for an unrelated server update', async () => {
        const options = serverQueryOptions('server');
        client.setQueryData(options.queryKey, server);
        const renders = vi.fn();
        const { result } = renderHook(
            () => {
                const permissions = useCurrentServerPermissions();
                renders(permissions);
                return permissions;
            },
            { wrapper }
        );
        const initialRenders = renders.mock.calls.length;
        await act(async () => {
            client.setQueryData(options.queryKey, { ...server, attributes: { ...server.attributes, name: 'Renamed' } });
            await new Promise((resolve) => setTimeout(resolve, 0));
        });
        expect(renders).toHaveBeenCalledTimes(initialRenders);
        await act(async () => {
            client.setQueryData(options.queryKey, {
                ...server,
                meta: { is_server_owner: false, user_permissions: ['console.read', 'control.start'] },
            });
        });
        await waitFor(() => expect(result.current).toEqual(['console.read', 'control.start']));
    });

    it('retains selected data and generated error types', () => {
        client.setQueryData(serverQueryOptions('server').queryKey, server);
        const { result } = renderHook(() => useServerQuery('server', (data) => data.attributes.internal_id), {
            wrapper,
        });
        expectTypeOf(result.current.data).toEqualTypeOf<number | undefined>();
        expectTypeOf(result.current.error).toEqualTypeOf<AxiosError<ClientGetServerError> | null>();
        expect(result.current.data).toBe(1);
    });

    it('prevents an older read from overwriting a completed mutation', async () => {
        const options = serverQueryOptions('server');
        client.setQueryData(options.queryKey, server);
        let finishRead = () => {};
        adapter.mockImplementation((config) => {
            const response = { config, headers: {}, status: 200, statusText: 'OK' };
            if (config.method === 'get') {
                return new Promise((resolve) => {
                    finishRead = () => resolve({ ...response, data: server });
                });
            }
            return Promise.resolve({ ...response, data: {} });
        });
        renderHook(() => useServerQuery('server'), { wrapper });
        const pendingRead = client.refetchQueries({ queryKey: options.queryKey });
        await waitFor(() => expect(adapter).toHaveBeenCalledOnce());
        const mutation = renderHook(() => useRenameServer('server'), { wrapper });
        await act(async () => {
            await mutation.result.current.mutateAsync({ path: { server_uuid: 'server' }, body: { name: 'Renamed' } });
            finishRead();
            await pendingRead;
        });
        expect(adapter.mock.calls[0]?.[0].signal?.aborted).toBe(true);
        expect(client.getQueryData(options.queryKey)?.attributes.name).toBe('Renamed');
    });

    it('does not let enabled override missing resource prerequisites', async () => {
        const { result } = renderHook(
            () => ({
                egg: useAdminEgg(Number.NaN, { enabled: true }),
                startup: useServerStartup('', { enabled: true }),
            }),
            { wrapper }
        );
        await act(async () => {
            await Promise.resolve();
        });
        expect(result.current.egg.fetchStatus).toBe('idle');
        expect(result.current.startup.fetchStatus).toBe('idle');
        expect(adapter).not.toHaveBeenCalled();
    });

    it('retains native polling with fresh cached data', async () => {
        const { result } = renderHook(() => useQuery({ ...serverQueryOptions('server'), refetchInterval: 10 }), {
            wrapper,
        });
        await waitFor(() => expect(result.current.data).toEqual(server));
        await waitFor(() => expect(adapter.mock.calls.length).toBeGreaterThan(1));
    });
});
