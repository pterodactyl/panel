/** @vitest-environment jsdom */
import { act, cleanup, fireEvent, render, screen, waitFor, within } from '@testing-library/react';
import { afterAll, afterEach, beforeAll, describe, expect, it, vi, type MockInstance } from 'vitest';
import { SocketEvent } from '@/components/server/events';
import { Websocket } from '@/plugins/Websocket';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { useServerStore, type ServerStatus } from '@/state/server';
import {
    createExtensionTestHost,
    createTestServer,
    type ExtensionTestHost,
    type TestServerOptions,
} from '@/sdk/testing';
import { clearThemeLayers, getViewDefinition, registerViewLayer } from '../registry';
import { View, ViewArea } from '../View';
import type { ServerGraphsModel } from './console';

vi.mock('ghostty-web', () => ({ Ghostty: { load: () => new Promise(() => {}) } }));

let host: ExtensionTestHost | undefined;
let portal: HTMLElement;
let send: MockInstance<Websocket['send']>;
let addListener: MockInstance<Websocket['addListener']>;

beforeAll(() => {
    vi.stubGlobal(
        'ResizeObserver',
        class {
            observe() {}
            disconnect() {}
        }
    );
    portal = document.body.appendChild(Object.assign(document.createElement('div'), { id: 'modal-portal' }));
});

afterAll(() => {
    portal.remove();
    vi.unstubAllGlobals();
});

afterEach(() => {
    cleanup();
    host?.dispose();
    host = undefined;
    vi.restoreAllMocks();
});

/** Stands in for the server area's websocket handler, which records the power state. */
function StatusListener() {
    const setServerStatus = useServerStore((state) => state.status.setServerStatus);

    useWebsocketEvent(SocketEvent.STATUS, (state) => setServerStatus(state as ServerStatus));

    return null;
}

async function renderServer(children: React.ReactNode, options: TestServerOptions = {}) {
    send = vi.spyOn(Websocket.prototype, 'send').mockReturnValue(true);
    addListener = vi.spyOn(Websocket.prototype, 'addListener');
    host = createExtensionTestHost({ server: createTestServer(options) });

    await act(async () => {
        render(
            <ViewArea area='server'>
                <StatusListener />
                {children}
            </ViewArea>,
            { wrapper: host!.Wrapper }
        );
    });

    return host;
}

const status = (state: string) => act(() => host!.emitWebsocket('status', state));

const stats = (overrides: Partial<{ cpu: number; memory: number; disk: number; uptime: number }> = {}) =>
    act(() =>
        host!.emitWebsocket(
            'stats',
            JSON.stringify({
                cpu_absolute: overrides.cpu ?? 42.5,
                memory_bytes: overrides.memory ?? 512 * 1024 ** 2,
                disk_bytes: overrides.disk ?? 256 * 1024 ** 2,
                uptime: overrides.uptime ?? 3_723_000,
                network: { rx_bytes: 2048, tx_bytes: 1024 },
            })
        )
    );

describe('server.power', () => {
    it('sends the signal of each button the user may press', async () => {
        await renderServer(<View name='server.power' />);
        await status('offline');

        fireEvent.click(screen.getByRole('button', { name: 'Start' }));
        expect(send).toHaveBeenCalledWith('set state', 'start');
        expect(screen.getByRole('button', { name: 'Stop' })).toBeDisabled();

        await status('running');
        expect(screen.getByRole('button', { name: 'Start' })).toBeDisabled();
        fireEvent.click(screen.getByRole('button', { name: 'Restart' }));
        expect(send).toHaveBeenLastCalledWith('set state', 'restart');
    });

    it('asks before killing a stopping server', async () => {
        await renderServer(<View name='server.power' />);
        await status('stopping');

        fireEvent.click(screen.getByRole('button', { name: 'Kill' }));
        const dialog = await screen.findByRole('dialog');

        expect(send).not.toHaveBeenCalledWith('set state', 'kill');
        fireEvent.click(within(dialog).getByRole('button', { name: 'Continue' }));
        expect(send).toHaveBeenCalledWith('set state', 'kill');
    });

    it('shows nothing but its slots without a power permission', async () => {
        await renderServer(<View name='server.power' />, { owner: false, permissions: ['file.read'] });

        expect(screen.queryByRole('button')).not.toBeInTheDocument();
    });
});

describe('server.stats', () => {
    it('reads the usage Wings reports once, however many times it renders', async () => {
        await renderServer(
            <>
                <div data-testid='header'>
                    <View name='server.stats' />
                </div>
                <View name='server.stats' />
            </>
        );

        expect(addListener.mock.calls.filter(([event]) => event === 'stats')).toHaveLength(1);
        expect(send).toHaveBeenCalledWith('send stats');

        await status('running');
        await stats();

        const header = within(screen.getByTestId('header'));

        expect(header.getByText('42.50%')).toBeVisible();
        expect(screen.getAllByText('512 MiB')).toHaveLength(2);
        expect(header.getByText('1h 2m 3s')).toBeVisible();
        expect(header.getByText('/ 100%')).toBeVisible();
    });

    it('flags usage close to the limits', async () => {
        await renderServer(<View name='server.stats' />);
        await status('running');
        await stats({ cpu: 95, memory: 870 * 1024 ** 2 });

        const block = (title: string) => screen.getByText(title).parentElement!.parentElement!;

        expect(block('CPU Load').querySelector('.bg-destructive')).not.toBeNull();
        expect(block('Memory').querySelector('.bg-warning')).not.toBeNull();
        expect(block('Disk').querySelector('.bg-warning, .bg-destructive')).toBeNull();
    });
});

describe('server.graphs', () => {
    it('collects samples while the server runs and clears them when it stops', async () => {
        let model: ServerGraphsModel | undefined;

        registerViewLayer('test-graphs', 'theme', {
            'server.graphs': (props: { model: ServerGraphsModel }) => {
                model = props.model;

                return null;
            },
        });

        try {
            await renderServer(<View name='server.graphs' />);
            await status('running');
            await stats({ cpu: 10 });
            await stats({ cpu: 20 });

            expect(model?.live).toBe(true);
            expect(model?.cpu.points.map((point) => point.value)).toEqual([10, 20]);
            expect(model?.memory.limit).toBe(1024);
            expect(model?.network.points.at(-1)).toMatchObject({ rx: 0, tx: 0 });

            await status('offline');
            expect(model?.live).toBe(false);
            expect(model?.cpu.points).toEqual([]);
        } finally {
            clearThemeLayers();
        }
    });
});

describe('server.terminal', () => {
    it('sends commands and steps through their history', async () => {
        vi.spyOn(console, 'error').mockImplementation(() => {});
        await renderServer(<View name='server.terminal' />);
        const input = screen.getByRole('textbox', { name: 'Console command input.' });

        fireEvent.change(input, { target: { value: 'say hello' } });
        fireEvent.keyDown(input, { key: 'Enter' });
        expect(send).toHaveBeenCalledWith('send command', 'say hello');
        expect(input).toHaveValue('');

        fireEvent.change(input, { target: { value: 'list' } });
        fireEvent.keyDown(input, { key: 'Enter' });
        fireEvent.keyDown(input, { key: 'ArrowUp' });
        expect(input).toHaveValue('list');
        fireEvent.keyDown(input, { key: 'ArrowUp' });
        expect(input).toHaveValue('say hello');
        fireEvent.keyDown(input, { key: 'ArrowDown' });
        fireEvent.keyDown(input, { key: 'ArrowDown' });
        expect(input).toHaveValue('');
    });

    it('has no command line without the console permission', async () => {
        await renderServer(<View name='server.terminal' />, { owner: false, permissions: ['control.start'] });

        expect(screen.queryByRole('textbox')).not.toBeInTheDocument();
    });
});

describe('server.features', () => {
    it('opens the EULA prompt when the output asks for it', async () => {
        await renderServer(<View name='server.features' />, { eggFeatures: ['EULA'] });
        await status('offline');

        expect(screen.queryByRole('dialog')).not.toBeInTheDocument();
        act(() => host!.emitWebsocket('console output', 'You need to agree to the EULA in order to run the server.'));

        expect(await screen.findByRole('dialog')).toHaveTextContent('Minecraft® EULA');
    });

    it('renders only the prompts the egg enables', () => {
        const fixture = getViewDefinition('server.features')!.fixtures!.none!();

        expect(fixture).toEqual({ views: [] });
    });
});

describe('page.server.console', () => {
    it('keeps the title while hidden, and composes the blocks', async () => {
        await renderServer(<View name='page.server.console' active={false} />);

        await waitFor(() => expect(screen.getByRole('heading', { name: 'Test server' })).toBeInTheDocument());
        expect(screen.getByRole('button', { name: 'Start' })).toBeInTheDocument();
        expect(screen.getByText('CPU Load', { selector: 'h3' })).toBeInTheDocument();
    });

    it('warns above the console while the server installs', async () => {
        await renderServer(<View name='page.server.console' active />, { status: 'installing' });

        expect(await screen.findByText(/running its installation process/)).toBeVisible();
    });
});
