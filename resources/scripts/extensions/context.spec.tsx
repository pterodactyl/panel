/** @vitest-environment jsdom */
import { StrictMode } from 'react';
import { act, cleanup, render } from '@testing-library/react';
import { afterEach, beforeEach, expect, it, vi } from 'vitest';
import { ExtensionContext, useExtensionCallback } from '@/extensions/context';
import { getExtensionStates } from '@/extensions/registry';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { SocketEvent } from '@/components/server/events';
import { Websocket } from '@/plugins/Websocket';

const connection = vi.hoisted(() => ({ connected: true, socket: null as Websocket | null }));
vi.mock('@/state/server', () => ({
    useSocketConnected: () => connection.connected,
    useSocketInstance: () => connection.socket,
}));
beforeEach(() => {
    connection.socket = new Websocket();
    connection.connected = true;
    vi.spyOn(console, 'error').mockImplementation(() => {});
});
afterEach(cleanup);

function Subscriber({ callback }: { callback: (value: string) => void | Promise<void> }) {
    useWebsocketEvent(SocketEvent.STATUS, useExtensionCallback(SocketEvent.STATUS, callback));
    return null;
}
const mountContext = { extensionId: 'event-mod', context: 'test mount' };

function Mount({ callback }: { callback: (value: string) => void | Promise<void> }) {
    return (
        <StrictMode>
            <ExtensionContext.Provider value={mountContext}>
                <Subscriber callback={callback} />
            </ExtensionContext.Provider>
        </StrictMode>
    );
}

it('isolates throwing and rejecting mod callbacks and reports their event', async () => {
    const result = render(
        <Mount
            callback={() => {
                throw new Error('sync failure');
            }}
        />
    );
    const healthy = vi.fn();
    connection.socket!.on('status', healthy);
    act(() => {
        connection.socket!.emit('status', 'running');
    });
    expect(healthy).toHaveBeenCalledWith('running');
    expect(getExtensionStates().find((state) => state.id === 'event-mod')?.error).toContain(
        'event "status": sync failure'
    );
    result.rerender(
        <Mount
            callback={async () => {
                throw new Error('async failure');
            }}
        />
    );
    await act(async () => {
        connection.socket!.emit('status', 'stopped');
    });
    expect(healthy).toHaveBeenCalledWith('stopped');
    expect(getExtensionStates().find((state) => state.id === 'event-mod')?.error).toContain(
        'event "status": async failure'
    );
});

it('uses the latest callback and cleans up across reconnects and Strict Mode', () => {
    const first = vi.fn();
    const latest = vi.fn();
    const result = render(<Mount callback={first} />);
    act(() => {
        connection.socket!.emit('status', 'first');
    });
    expect(first).toHaveBeenCalledTimes(1);
    result.rerender(<Mount callback={latest} />);
    act(() => {
        connection.socket!.emit('status', 'latest');
    });
    expect(first).toHaveBeenCalledTimes(1);
    expect(latest).toHaveBeenCalledTimes(1);
    connection.connected = false;
    result.rerender(<Mount callback={latest} />);
    act(() => {
        connection.socket!.emit('status', 'disconnected');
    });
    expect(latest).toHaveBeenCalledTimes(1);
    connection.connected = true;
    result.rerender(<Mount callback={latest} />);
    act(() => {
        connection.socket!.emit('status', 'reconnected');
    });
    expect(latest).toHaveBeenCalledTimes(2);
    result.unmount();
    act(() => {
        connection.socket!.emit('status', 'unmounted');
    });
    expect(latest).toHaveBeenCalledTimes(2);
});
