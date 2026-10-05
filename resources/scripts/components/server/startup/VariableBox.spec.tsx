// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import http from '@/api/http';
import type { ServerStartupVariable } from '@/api/server/startup/queries';
import VariableBox from './VariableBox';

vi.mock('@/api/server/queries', () => ({ useCurrentServerUuid: () => 'server' }));
vi.mock('@/plugins/usePermissions', () => ({ usePermissions: () => [true] }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: vi.fn() }));

const variable = (value: string): ServerStartupVariable => ({
    object: 'egg_variable',
    attributes: {
        name: 'Port',
        description: '',
        env_variable: 'PORT',
        default_value: '25565',
        server_value: value,
        is_editable: true,
        rules: 'required|string',
    },
    meta: { startup_command: `start ${value}`, raw_startup_command: 'start {{PORT}}' },
});
const originalAdapter = http.defaults.adapter;
let saves: string[];
let client: QueryClient;

beforeEach(() => {
    vi.useFakeTimers();
    saves = [];
    client = new QueryClient();
    http.defaults.adapter = (async (config) => {
        const { value } = JSON.parse(String(config.data)) as { value: string };
        saves.push(value);
        return { config, headers: {}, status: 200, statusText: 'OK', data: variable(value) };
    }) satisfies AxiosAdapter;
});

afterEach(() => {
    cleanup();
    client.clear();
    http.defaults.adapter = originalAdapter;
    vi.useRealTimers();
});

it('saves the edited value as soon as the field loses focus', async () => {
    render(
        <QueryClientProvider client={client}>
            <VariableBox variable={variable('25565')} />
        </QueryClientProvider>
    );
    const input = screen.getByRole('textbox');

    fireEvent.change(input, { target: { value: '25566' } });
    fireEvent.blur(input);
    await act(async () => {
        await vi.advanceTimersByTimeAsync(1);
    });
    expect(saves).toEqual(['25566']);

    await act(async () => {
        await vi.advanceTimersByTimeAsync(500);
    });
    expect(saves).toEqual(['25566']);
});
