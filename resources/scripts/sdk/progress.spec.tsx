// @vitest-environment jsdom
import { act, cleanup, renderHook, waitFor } from '@testing-library/react';
import { QueryClientProvider, onlineManager } from '@tanstack/react-query';
import { afterEach, expect, it, vi } from 'vitest';
import { AxiosError } from 'axios';
import type { ReactNode } from 'react';
import http from '@/api/http';
import { queryClient } from '@/api/queryClient';
import { ExtensionContext } from '@/extensions/context';
import { extensionProgressQueryOptions, useExtensionJobProgress } from './progress';

const mount = { extensionId: 'probe', context: 'progress test' };
const originalAdapter = http.defaults.adapter;
function Wrapper({ children }: { children: ReactNode }) {
    return (
        <QueryClientProvider client={queryClient}>
            <ExtensionContext.Provider value={mount}>{children}</ExtensionContext.Provider>
        </QueryClientProvider>
    );
}
afterEach(() => {
    cleanup();
    queryClient.clear();
    onlineManager.setOnline(true);
    http.defaults.adapter = originalAdapter;
    vi.useRealTimers();
});

it('recovers the latest server snapshot on reconnect with namespace and subject isolation', async () => {
    let sequence = 1;
    let requests = 0;
    http.defaults.adapter = async (config) => {
        requests++;
        expect(config.url).toBe('/api/client/servers/alpha/extension-progress/probe/job');
        expect(config.signal).toBeInstanceOf(AbortSignal);
        return {
            data: {
                data: {
                    id: 'job',
                    extension: 'probe',
                    status: sequence === 1 ? 'running' : 'completed',
                    percent: 25,
                    message: 'Working',
                    sequence,
                    updated_at: '2026-10-02T00:00:00Z',
                },
            },
            config,
            status: 200,
            statusText: 'OK',
            headers: {},
        };
    };
    const { result } = renderHook(() => useExtensionJobProgress('job', { serverUuid: 'alpha' }), { wrapper: Wrapper });
    await waitFor(() => expect(result.current.data?.sequence).toBe(1));
    expect(queryClient.getQueryData(extensionProgressQueryOptions('other', 'job', 'alpha').queryKey)).toBeUndefined();
    expect(queryClient.getQueryData(extensionProgressQueryOptions('probe', 'job').queryKey)).toBeUndefined();
    sequence = 2;
    act(() => {
        onlineManager.setOnline(false);
        onlineManager.setOnline(true);
    });
    await waitFor(() => expect(result.current.data?.sequence).toBe(2));
    expect(result.current.data?.status).toBe('completed');
    vi.useFakeTimers();
    await act(() => vi.advanceTimersByTimeAsync(5000));
    expect(requests).toBe(2);
});

it('stops polling and retrying when the job has expired or permission was revoked', async () => {
    let requests = 0;
    http.defaults.adapter = async (config) => {
        requests++;
        throw new AxiosError('Not found', 'ERR_BAD_REQUEST', config, undefined, {
            data: {},
            config,
            status: 404,
            statusText: 'Not found',
            headers: {},
        });
    };
    const { result } = renderHook(() => useExtensionJobProgress('expired'), { wrapper: Wrapper });
    await waitFor(() => expect(result.current.isError).toBe(true));
    vi.useFakeTimers();
    await act(() => vi.advanceTimersByTimeAsync(5000));
    expect(requests).toBe(1);
});
