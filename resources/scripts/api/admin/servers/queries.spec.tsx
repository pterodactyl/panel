// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, renderHook } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import {
    adminServerDatabasesQueryOptions,
    adminServerQueryOptions,
    createAdminServerInput,
    deleteAdminServerInput,
    forceDeleteAdminServerInput,
    transferAdminServerInput,
    useAdminServer,
    useCreateAdminServer,
    useDeleteAdminServer,
    useForceDeleteAdminServer,
    useTransferAdminServer,
    type AdminServer,
} from '@/api/admin/servers/queries';
import {
    adminNodeAllocationsQueryOptions,
    adminNodeConfigurationQueryOptions,
    adminNodeQueryOptions,
    updateAdminNodeInput,
    useUpdateAdminNode,
    type NodeValues,
} from '@/api/admin/nodes/queries';

vi.mock('sonner', () => ({ toast: { success: vi.fn() } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);
const server = { object: 'server', attributes: { id: 7, node: 3, name: 'Alpha' } } as AdminServer;
const nodeReads = (nodeId: number) => [
    adminNodeAllocationsQueryOptions(nodeId, 1).queryKey,
    adminNodeQueryOptions(nodeId).queryKey,
];
const isInvalidated = (key: readonly unknown[]) => client.getQueryState(key)?.isInvalidated;
const seed = <TData,>(key: readonly unknown[], data: TData) => client.setQueryData([...key], data);

describe('admin server cache', () => {
    beforeEach(() => {
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 30_000 } } });
        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: config.method === 'delete' || config.url?.endsWith('/transfer') ? 204 : 200,
            statusText: 'OK',
            data: config.method === 'delete' ? '' : server,
        }));
        client.setQueryData(adminServerQueryOptions(7).queryKey, server);
        seed(adminServerDatabasesQueryOptions(7).queryKey, { object: 'list', data: [] });
        for (const nodeId of [3, 4, 5]) {
            for (const key of nodeReads(nodeId)) {
                seed(key, { object: 'list', data: [] });
            }
        }
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
    });

    it.each([
        ['safely', useDeleteAdminServer, deleteAdminServerInput],
        ['forcibly', useForceDeleteAdminServer, forceDeleteAdminServerInput],
    ] as const)(
        'keeps a %s deleted server cached while its page is mounted and drops it once unmounted',
        async (_mode, useDelete, input) => {
            const detail = renderHook(() => useAdminServer(7), { wrapper });
            const deletion = renderHook(() => useDelete(), { wrapper });

            await act(async () => {
                await deletion.result.current.mutateAsync(input(7, 'Alpha'));
            });
            detail.rerender();
            await act(async () => {
                await Promise.resolve();
            });

            expect(adapter.mock.calls.filter(([config]) => config.method === 'get')).toHaveLength(0);
            expect(detail.result.current.data).toEqual(server);
            expect(client.getQueryData(adminServerDatabasesQueryOptions(7).queryKey)).toBeUndefined();

            detail.unmount();
            expect(client.getQueryData(adminServerQueryOptions(7).queryKey)).toBeUndefined();
            for (const key of nodeReads(3)) {
                expect(isInvalidated(key)).toBe(true);
            }

            for (const key of nodeReads(4)) {
                expect(isInvalidated(key)).toBe(false);
            }
        }
    );

    it('refreshes the allocations of the node a new server was placed on', async () => {
        const creation = renderHook(() => useCreateAdminServer(), { wrapper });

        await act(async () => {
            await creation.result.current.mutateAsync(
                createAdminServerInput({} as Parameters<typeof createAdminServerInput>[0])
            );
        });

        for (const key of nodeReads(3)) {
            expect(isInvalidated(key)).toBe(true);
        }

        for (const key of nodeReads(4)) {
            expect(isInvalidated(key)).toBe(false);
        }
    });

    it('refreshes the allocations of the source and destination nodes of a transfer', async () => {
        const transfer = renderHook(() => useTransferAdminServer(), { wrapper });

        await act(async () => {
            await transfer.result.current.mutateAsync(transferAdminServerInput(7, { node_id: 4, allocation_id: 1 }));
        });

        for (const key of [...nodeReads(3), ...nodeReads(4)]) {
            expect(isInvalidated(key)).toBe(true);
        }

        for (const key of nodeReads(5)) {
            expect(isInvalidated(key)).toBe(false);
        }
    });

    it('refreshes the node configuration after a node update', async () => {
        seed(adminNodeConfigurationQueryOptions(3).queryKey, 'token: revoked');
        seed(adminNodeConfigurationQueryOptions(4).queryKey, 'token: other');
        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: { object: 'node', attributes: { id: 3, name: 'Node' } },
        }));
        const update = renderHook(() => useUpdateAdminNode(), { wrapper });

        await act(async () => {
            await update.result.current.mutateAsync(updateAdminNodeInput(3, {} as NodeValues, true));
        });

        expect(isInvalidated(adminNodeConfigurationQueryOptions(3).queryKey)).toBe(true);
        expect(isInvalidated(adminNodeConfigurationQueryOptions(4).queryKey)).toBe(false);
    });
});
