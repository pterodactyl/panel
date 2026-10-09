// @vitest-environment jsdom

import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { act, cleanup, fireEvent, render, screen } from '@testing-library/react';
import type { AxiosAdapter } from 'axios';
import type { PropsWithChildren } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import http from '@/api/http';
import VariableBox from '@/components/server/startup/VariableBox';
import { serverStartupQueryOptions, useServerStartup, type ServerStartupVariable } from './queries';

const mocks = vi.hoisted(() => ({ uuid: 'server', error: vi.fn() }));

vi.mock('@/api/server/queries', () => ({
    useCurrentServerUuid: () => mocks.uuid,
    useUpdateCurrentServer: () => vi.fn(),
}));
vi.mock('@/plugins/usePermissions', () => ({ usePermissions: () => [true] }));
vi.mock('@/plugins/notifications', () => ({ notifyHttpError: mocks.error }));
vi.mock('@/components/elements/InputSpinner', () => ({
    default: ({ visible, children }: PropsWithChildren<{ visible: boolean }>) => (
        <div>
            <span role='status'>{visible ? 'Saving' : 'Idle'}</span>
            {children}
        </div>
    ),
}));

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
const adapter = vi.fn<AxiosAdapter>();
const originalAdapter = http.defaults.adapter;
let client: QueryClient;
let saves: { value: string; finish: () => void; fail: () => void }[];
const wrapper = ({ children }: PropsWithChildren) => (
    <QueryClientProvider client={client}>{children}</QueryClientProvider>
);

function Editor({ uuid }: { uuid: string }) {
    const { data } = useServerStartup(uuid);

    return data?.data[0] ? <VariableBox variable={data.data[0]} /> : null;
}

const advance = (ms = 1) =>
    act(async () => {
        await vi.advanceTimersByTimeAsync(ms);
    });

describe('startup autosaves', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.clearAllMocks();
        mocks.uuid = 'server';
        saves = [];
        client = new QueryClient({ defaultOptions: { queries: { staleTime: 30_000, retry: false } } });
        for (const uuid of ['server', 'other']) {
            client.setQueryData(serverStartupQueryOptions(uuid).queryKey, {
                object: 'list',
                data: [variable('original')],
                meta: { startup_command: 'start original', raw_startup_command: 'start {{PORT}}', docker_images: {} },
            });
        }

        http.defaults.adapter = adapter;
        adapter.mockImplementation(
            (config) =>
                new Promise((resolve, reject) => {
                    const { value } = JSON.parse(String(config.data)) as { value: string };

                    saves.push({
                        value,
                        finish: () =>
                            resolve({ config, headers: {}, status: 200, statusText: 'OK', data: variable(value) }),
                        fail: () => reject(new Error('Save failed')),
                    });
                })
        );
    });

    afterEach(() => {
        cleanup();
        client.clear();
        http.defaults.adapter = originalAdapter;
        vi.useRealTimers();
    });

    it('serializes saves and preserves the newest draft while earlier saves finish', async () => {
        render(<Editor uuid='server' />, { wrapper });
        const input = screen.getByRole('textbox');

        fireEvent.change(input, { target: { value: 'first' } });
        await advance(500);
        expect(saves).toHaveLength(1);
        fireEvent.change(input, { target: { value: 'second' } });
        await advance(500);
        expect(saves).toHaveLength(1);
        expect(input).toHaveValue('second');
        expect(screen.getByRole('status')).toHaveTextContent('Saving');

        saves[0]!.finish();
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['first', 'second']);
        expect(input).toHaveValue('second');
        expect(screen.getByRole('status')).toHaveTextContent('Saving');
        saves[1]!.finish();
        await advance();
        expect(input).toHaveValue('second');
        expect(screen.getByRole('status')).toHaveTextContent('Idle');
        expect(client.getQueryData(serverStartupQueryOptions('server').queryKey)?.meta?.startup_command).toBe(
            'start second'
        );
    });

    it('keeps failed drafts and saves edits made without a keyboard event', async () => {
        render(<Editor uuid='server' />, { wrapper });
        const input = screen.getByRole('textbox');

        fireEvent.input(input, { target: { value: 'pasted' } });
        await advance(500);
        expect(saves[0]?.value).toBe('pasted');
        saves[0]!.fail();
        await advance();
        expect(input).toHaveValue('pasted');
        expect(screen.getByRole('status')).toHaveTextContent('Idle');
        expect(mocks.error).toHaveBeenCalledOnce();
        fireEvent.input(input, { target: { value: 'corrected' } });
        await advance(500);
        saves[1]!.finish();
        await advance();
        expect(input).toHaveValue('corrected');
    });

    it('saves unsent edits to their original server on resource change and unmount', async () => {
        const view = render(<Editor uuid='server' />, { wrapper });

        fireEvent.change(screen.getByRole('textbox'), { target: { value: 'submitted' } });
        await advance(500);
        fireEvent.change(screen.getByRole('textbox'), { target: { value: 'unsent' } });
        mocks.uuid = 'other';
        view.rerender(<Editor uuid='other' />);
        saves[0]!.finish();
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['submitted', 'unsent']);
        saves[1]!.finish();
        await advance();
        expect(screen.getByRole('textbox')).toHaveValue('original');
        expect(
            client.getQueryData(serverStartupQueryOptions('server').queryKey)?.data[0]?.attributes.server_value
        ).toBe('unsent');
        expect(client.getQueryData(serverStartupQueryOptions('other').queryKey)?.data[0]?.attributes.server_value).toBe(
            'original'
        );
        fireEvent.change(screen.getByRole('textbox'), { target: { value: 'flushed' } });
        view.unmount();
        await advance();
        expect(saves.map((save) => save.value)).toEqual(['submitted', 'unsent', 'flushed']);
    });
});
