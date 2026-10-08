/** @vitest-environment jsdom */
import { cleanup, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it, type MockInstance, vi } from 'vitest';
import { Terminal } from 'ghostty-web';
import { Websocket } from '@/plugins/Websocket';
import { useConsoleSocketLogs } from './useConsoleSocketLogs';

const screen = vi.hoisted(() => ({ lines: [] as string[], clears: 0 }));

vi.mock('ghostty-web', () => ({
    Terminal: class {
        clear() {
            screen.clears++;
            screen.lines.length = 0;
        }
        writeln(line: string) {
            screen.lines.push(line);
        }
    },
}));

describe('useConsoleSocketLogs', () => {
    let socket: Websocket;
    let send: MockInstance<Websocket['send']>;
    const terminal = { current: new Terminal() };

    const mount = () =>
        renderHook(
            (props: { connected: boolean }) =>
                useConsoleSocketLogs({ connected: props.connected, instance: socket, terminal, terminalReady: true }),
            { initialProps: { connected: true } }
        );

    beforeEach(() => {
        vi.useFakeTimers();
        screen.lines.length = 0;
        screen.clears = 0;
        socket = new Websocket();
        send = vi.spyOn(socket, 'send').mockReturnValue(true);
    });

    afterEach(() => {
        cleanup();
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    it('clears the terminal and requests the log history once per connection', () => {
        mount();
        socket.emit('console output', 'history line');
        vi.advanceTimersByTime(30_000);

        expect(screen.clears).toBe(1);
        expect(send).toHaveBeenCalledTimes(1);
        expect(send).toHaveBeenCalledWith('send logs');
        expect(screen.lines).toEqual([expect.stringContaining('history line')]);
    });

    it('requests the history again only after Wings reports throttling it', () => {
        mount();

        socket.emit('throttled', 'send command');
        vi.advanceTimersByTime(30_000);
        expect(send).toHaveBeenCalledTimes(1);

        socket.emit('throttled', 'send logs');
        vi.advanceTimersByTime(4_999);
        expect(send).toHaveBeenCalledTimes(1);
        vi.advanceTimersByTime(1);

        expect(send).toHaveBeenCalledTimes(2);
        expect(screen.clears).toBe(2);
    });

    it('restores transfer output after a reconnect without duplicating the history', () => {
        const { rerender } = mount();

        socket.emit('console output', 'history line');
        socket.emit('transfer logs', 'Streaming archive to destination...');
        socket.emit('transfer status', 'failure');

        rerender({ connected: false });
        rerender({ connected: true });
        socket.emit('console output', 'history line');

        expect(send).toHaveBeenCalledTimes(2);
        expect(screen.lines).toEqual([
            expect.stringContaining('Streaming archive to destination...'),
            expect.stringContaining('Transfer has failed.'),
            expect.stringContaining('history line'),
        ]);
    });
});
