// @vitest-environment jsdom
import { QueryClientProvider } from '@tanstack/react-query';
import { act, renderHook } from '@testing-library/react';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import type { AxiosAdapter } from 'axios';
import http from '@/api/http';
import { queryClient } from '@/api/queryClient';
import { useSendServerCommand, useSendServerPower } from './mutations';

const notifyHttpError = vi.hoisted(() => vi.fn());

vi.mock('@/plugins/notifications', () => ({ notifyHttpError }));

const originalAdapter = http.defaults.adapter;
let requests: { method?: string; path: string; body: unknown }[];

beforeEach(() => {
    requests = [];
    notifyHttpError.mockClear();
});
afterEach(() => {
    queryClient.clear();
    http.defaults.adapter = originalAdapter;
});
const respond = (accepted: boolean) => {
    const adapter: AxiosAdapter = async (config) => {
        requests.push({
            method: config.method,
            path: new URL(config.url!, 'https://panel.test').pathname,
            body: JSON.parse(String(config.data)),
        });
        if (!accepted) {
            throw new Error('Bad Gateway');
        }

        return { data: '', config, status: 204, statusText: 'No Content', headers: {} };
    };

    http.defaults.adapter = adapter;
};

const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
);

it('sends power signals and console commands through the core client endpoints', async () => {
    respond(true);
    const power = renderHook(() => useSendServerPower('alpha'), { wrapper });
    const command = renderHook(() => useSendServerCommand('alpha'), { wrapper });

    await act(() => power.result.current.mutateAsync({ signal: 'restart' }));
    await act(() => command.result.current.mutateAsync({ command: 'say hello' }));

    expect(requests).toEqual([
        { method: 'post', path: '/api/client/servers/alpha/power', body: { signal: 'restart' } },
        { method: 'post', path: '/api/client/servers/alpha/command', body: { command: 'say hello' } },
    ]);
    expect(notifyHttpError).not.toHaveBeenCalled();
});

it('rejects and reports through the core notification when Wings refuses a command', async () => {
    respond(false);
    const command = renderHook(() => useSendServerCommand('alpha'), { wrapper });

    await act(async () => {
        await expect(command.result.current.mutateAsync({ command: 'list' })).rejects.toThrow('Bad Gateway');
    });

    expect(notifyHttpError).toHaveBeenCalledWith(expect.anything(), 'Unable to send command');
});
