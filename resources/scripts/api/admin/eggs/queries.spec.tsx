// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, renderHook, waitFor } from '@testing-library/react';
import type { AxiosAdapter, AxiosResponse } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import { adminGetEggQueryKey } from '@/api/generated/@tanstack/react-query.gen';
import {
    adminEggQueryOptions,
    adminEggVariablesQueryOptions,
    createAdminEggVariableInput,
    updateAdminEggScriptInput,
    updateAdminEggVariableInput,
    useAdminEggVariables,
    useCreateAdminEggVariable,
    useUpdateAdminEggScript,
    useUpdateAdminEggVariable,
} from './queries';

const notifications = vi.hoisted(() => ({ success: vi.fn(), error: vi.fn() }));

vi.mock('sonner', () => ({ toast: { success: notifications.success } }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: notifications.error }));

const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);
const input = createAdminEggVariableInput(1, {
    name: 'Port',
    description: '',
    env_variable: 'GAME_PORT',
    default_value: '25565',
    options: ['user_viewable', 'user_editable'],
    rules: 'required|string',
});
const created = { object: 'egg_variable', attributes: { id: 1, name: 'Port' } };

describe('mutation cache reconciliation', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        adapter.mockReset();
        http.defaults.adapter = adapter;
        client = new QueryClient({ defaultOptions: { queries: { staleTime: 30_000, retry: false } } });
        client.setQueryData(adminEggVariablesQueryOptions(1).queryKey, { object: 'list', data: [] });
        client.setQueryData(adminEggVariablesQueryOptions(2).queryKey, { object: 'list', data: [] });
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
    });

    it('stays pending through the list refresh even if the initiating component unmounts', async () => {
        let finishRefresh = () => {};

        adapter.mockImplementation((config) => {
            const response = { config, headers: {}, status: 200, statusText: 'OK' };

            if (config.method === 'get') {
                return new Promise<AxiosResponse>((resolve) => {
                    finishRefresh = () => resolve({ ...response, data: { object: 'list', data: [created] } });
                });
            }

            return Promise.resolve({ ...response, data: created });
        });
        const list = renderHook(() => useAdminEggVariables(1), { wrapper });
        const mutation = renderHook(() => useCreateAdminEggVariable(), { wrapper });
        const finished = vi.fn();
        let saving: Promise<unknown>;

        act(() => {
            saving = mutation.result.current.mutateAsync(input).then(finished);
        });
        await waitFor(() => expect(adapter).toHaveBeenCalledTimes(2));
        expect(mutation.result.current.isPending).toBe(true);
        expect(finished).not.toHaveBeenCalled();
        expect(client.getQueryState(adminEggVariablesQueryOptions(2).queryKey)?.isInvalidated).toBe(false);
        mutation.unmount();

        await act(async () => {
            finishRefresh();
            await saving;
        });

        await waitFor(() => expect(list.result.current.data?.data).toEqual([created]));
        expect(finished).toHaveBeenCalledOnce();
        expect(notifications.success).toHaveBeenCalledOnce();
        expect(notifications.error).not.toHaveBeenCalled();
    });

    it('refreshes every cached read of the egg, whatever it includes, after a variable or script change', async () => {
        const reads = [
            adminEggQueryOptions(1).queryKey,
            adminGetEggQueryKey({ path: { egg_id: 1 }, query: { include: 'variables' } }),
        ];
        const otherEgg = adminEggQueryOptions(2).queryKey;
        const seed = () => {
            for (const key of [...reads, otherEgg]) {
                client.setQueryData(key, { object: 'egg', attributes: { id: 1 } });
            }
        };

        adapter.mockImplementation(async (config) => ({
            config,
            headers: {},
            status: 200,
            statusText: 'OK',
            data: { object: 'egg', attributes: { id: 1, name: 'Paper' } },
        }));
        const variable = renderHook(() => useUpdateAdminEggVariable(), { wrapper });
        const script = renderHook(() => useUpdateAdminEggScript(), { wrapper });

        seed();
        await act(async () => {
            await variable.result.current.mutateAsync(
                updateAdminEggVariableInput(1, 5, { name: 'Port', env_variable: 'PORT', rules: 'required' })
            );
        });
        for (const key of reads) {
            expect(client.getQueryState(key)?.isInvalidated).toBe(true);
        }

        expect(client.getQueryState(otherEgg)?.isInvalidated).toBe(false);

        client.clear();
        seed();
        await act(async () => {
            await script.result.current.mutateAsync(
                updateAdminEggScriptInput(1, {
                    script_install: 'echo',
                    script_entry: 'bash',
                    script_container: 'alpine',
                })
            );
        });
        for (const key of reads) {
            expect(client.getQueryState(key)?.isInvalidated).toBe(true);
        }

        expect(client.getQueryState(otherEgg)?.isInvalidated).toBe(false);
    });

    it('reports a refresh error without turning a successful write into a failed creation', async () => {
        adapter.mockImplementation(async (config) => {
            if (config.method === 'get') {
                throw new Error('Refresh failed');
            }

            return { config, headers: {}, status: 200, statusText: 'OK', data: created };
        });
        const list = renderHook(() => useAdminEggVariables(1), { wrapper });
        const mutation = renderHook(() => useCreateAdminEggVariable(), { wrapper });

        await act(async () => {
            await expect(mutation.result.current.mutateAsync(input)).resolves.toEqual(created);
        });
        await waitFor(() => expect(list.result.current.error?.message).toBe('Refresh failed'));
        expect(adapter.mock.calls.filter(([config]) => config.method === 'post')).toHaveLength(1);
        expect(notifications.success).toHaveBeenCalledOnce();
        expect(notifications.error).not.toHaveBeenCalled();
    });
});
