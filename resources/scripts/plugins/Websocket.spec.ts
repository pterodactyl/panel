/** @vitest-environment jsdom */
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import {
    MAX_RECONNECT_ATTEMPTS,
    RECONNECT_BASE_DELAY_MS,
    RECONNECT_MAX_DELAY_MS,
    reconnectDelay,
    Websocket,
} from './Websocket';

class FakeSocket {
    static instances: FakeSocket[] = [];

    onclose: ((event: CloseEvent) => void) | null = null;
    onerror: ((event: Event) => void) | null = null;
    onmessage: ((event: MessageEvent<string>) => void) | null = null;
    onopen: (() => void) | null = null;
    readyState: WebSocket['readyState'] = 0;
    sent: string[] = [];
    closedWith: { code?: number; reason?: string } | null = null;

    constructor(public readonly url: string) {
        FakeSocket.instances.push(this);
    }

    send(data: string) {
        if (this.readyState !== 1) {
            throw new DOMException('Still in CONNECTING state.', 'InvalidStateError');
        }
        this.sent.push(data);
    }

    close(code?: number, reason?: string) {
        this.readyState = 3;
        this.closedWith = { code, reason };
    }

    open() {
        this.readyState = 1;
        this.onopen?.();
    }

    message<T>(data: T) {
        this.onmessage?.({ data: JSON.stringify(data) } as MessageEvent<string>);
    }

    closeFromServer(code: number) {
        this.readyState = 3;
        this.onclose?.({ code } as CloseEvent);
    }
}

const latest = () => FakeSocket.instances[FakeSocket.instances.length - 1];

const authFrame = (token: string) => JSON.stringify({ event: 'auth', args: [token] });

describe('Websocket', () => {
    beforeEach(() => {
        FakeSocket.instances = [];
        Websocket.createSocket = (url) => new FakeSocket(url);
        vi.spyOn(Math, 'random').mockReturnValue(1);
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
        Websocket.createSocket = (url) => new WebSocket(url);
    });

    it('authenticates after opening', () => {
        const socket = new Websocket();
        const onOpen = vi.fn();

        socket.on('SOCKET_OPEN', onOpen);
        socket.setToken('token-value').connect('wss://example.test/socket');

        const native = FakeSocket.instances[0];
        native.open();

        expect(onOpen).toHaveBeenCalledOnce();
        expect(native.sent).toEqual([authFrame('token-value')]);
    });

    it('dispatches daemon events with args', () => {
        const socket = new Websocket();
        const listener = vi.fn();

        socket.on('console output', listener);
        socket.connect('wss://example.test/socket');
        FakeSocket.instances[0].message({ event: 'console output', args: ['line one', true] });

        expect(listener).toHaveBeenCalledWith('line one', true);
    });

    it('drops sends while the socket is not open and authenticates with the latest token once it opens', () => {
        const socket = new Websocket();
        socket.setToken('first').connect('wss://example.test/socket');

        expect(() => socket.setToken('refreshed', true)).not.toThrow();
        expect(socket.send('send logs')).toBe(false);
        expect(FakeSocket.instances[0].sent).toEqual([]);

        FakeSocket.instances[0].open();

        expect(FakeSocket.instances[0].sent).toEqual([authFrame('refreshed')]);
        expect(socket.send('send logs')).toBe(true);
    });

    it('reconnects abnormal closes', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        const onReconnect = vi.fn();

        socket.on('SOCKET_RECONNECT', onReconnect);
        socket.connect('wss://example.test/socket');
        FakeSocket.instances[0].closeFromServer(1006);

        expect(onReconnect).toHaveBeenCalledOnce();
        expect(FakeSocket.instances).toHaveLength(1);

        vi.advanceTimersByTime(RECONNECT_BASE_DELAY_MS);

        expect(FakeSocket.instances).toHaveLength(2);
        expect(FakeSocket.instances[1].url).toBe('wss://example.test/socket');
    });

    it('backs off exponentially while sockets open but never authenticate', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        socket.setToken('token').connect('wss://example.test/socket');

        for (const delay of [1_000, 2_000, 4_000, 8_000]) {
            latest().open();
            latest().closeFromServer(1006);
            const count = FakeSocket.instances.length;

            vi.advanceTimersByTime(delay - 1);
            expect(FakeSocket.instances).toHaveLength(count);
            vi.advanceTimersByTime(1);
            expect(FakeSocket.instances).toHaveLength(count + 1);
        }
    });

    it('restarts the backoff only after the daemon accepts authentication', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        socket.setToken('token').connect('wss://example.test/socket');

        latest().closeFromServer(1006);
        vi.advanceTimersByTime(1_000);
        latest().closeFromServer(1006);
        vi.advanceTimersByTime(2_000);

        latest().open();
        latest().message({ event: 'auth success' });
        latest().closeFromServer(1006);
        const count = FakeSocket.instances.length;

        vi.advanceTimersByTime(1_000);
        expect(FakeSocket.instances).toHaveLength(count + 1);
    });

    it('gives up after the attempt budget and resumes once the browser is back online', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        const onGiveUp = vi.fn();
        socket.on('SOCKET_CONNECT_ERROR', onGiveUp);
        socket.connect('wss://example.test/socket');

        for (let attempt = 1; attempt <= MAX_RECONNECT_ATTEMPTS; attempt++) {
            latest().closeFromServer(1006);
            vi.advanceTimersByTime(reconnectDelay(attempt));
        }
        latest().closeFromServer(1006);
        vi.runOnlyPendingTimers();

        expect(onGiveUp).toHaveBeenCalledOnce();
        expect(FakeSocket.instances).toHaveLength(MAX_RECONNECT_ATTEMPTS + 1);

        window.dispatchEvent(new Event('online'));

        expect(FakeSocket.instances).toHaveLength(MAX_RECONNECT_ATTEMPTS + 2);

        latest().closeFromServer(1006);
        vi.advanceTimersByTime(RECONNECT_BASE_DELAY_MS);
        expect(FakeSocket.instances).toHaveLength(MAX_RECONNECT_ATTEMPTS + 3);
    });

    it('waits for the browser to come back online instead of spending attempts while offline', () => {
        vi.useFakeTimers();
        const onLine = vi.spyOn(navigator, 'onLine', 'get').mockReturnValue(false);
        const socket = new Websocket();
        socket.connect('wss://example.test/socket');
        latest().closeFromServer(1006);
        vi.runAllTimers();

        expect(FakeSocket.instances).toHaveLength(1);

        onLine.mockReturnValue(true);
        document.dispatchEvent(new Event('visibilitychange'));

        expect(FakeSocket.instances).toHaveLength(2);
    });

    it('does not reconnect suspended-node close codes', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        const onReconnect = vi.fn();

        socket.on('SOCKET_RECONNECT', onReconnect);
        socket.connect('wss://example.test/socket');
        FakeSocket.instances[0].closeFromServer(4409);
        vi.runAllTimers();

        expect(onReconnect).not.toHaveBeenCalled();
        expect(FakeSocket.instances).toHaveLength(1);
    });

    it('stops reconnecting and ignores the replaced socket once closed', () => {
        vi.useFakeTimers();
        const socket = new Websocket();
        const onClose = vi.fn();
        socket.on('SOCKET_CLOSE', onClose);
        socket.connect('wss://example.test/socket');
        const native = latest();

        socket.close();
        native.closeFromServer(1006);
        window.dispatchEvent(new Event('online'));
        vi.runAllTimers();

        expect(native.closedWith).not.toBeNull();
        expect(onClose).not.toHaveBeenCalled();
        expect(FakeSocket.instances).toHaveLength(1);
    });
});

describe('reconnectDelay', () => {
    it('draws from the upper half of an exponentially growing, capped window', () => {
        expect(reconnectDelay(1, () => 0)).toBe(RECONNECT_BASE_DELAY_MS / 2);
        expect(reconnectDelay(1, () => 1)).toBe(RECONNECT_BASE_DELAY_MS);
        expect(reconnectDelay(3, () => 1)).toBe(RECONNECT_BASE_DELAY_MS * 4);
        expect(reconnectDelay(50, () => 0)).toBe(RECONNECT_MAX_DELAY_MS / 2);
        expect(reconnectDelay(50, () => 1)).toBe(RECONNECT_MAX_DELAY_MS);
    });
});

it('continues event delivery when a listener throws and attributes dispatch errors correctly', () => {
    const errors = vi.spyOn(console, 'error').mockImplementation(() => {});
    const warnings = vi.spyOn(console, 'warn').mockImplementation(() => {});
    Websocket.createSocket = (url) => new FakeSocket(url);
    const socket = new Websocket();
    socket.on('status', () => {
        throw new Error('broken listener');
    });
    const healthy = vi.fn();
    socket.on('status', healthy);
    socket.connect('wss://example.test/socket');
    FakeSocket.instances.at(-1)!.message({ event: 'status', args: ['running'] });
    expect(healthy).toHaveBeenCalledWith('running');
    expect(errors).toHaveBeenCalledWith('Websocket listener failed for "status".', expect.any(Error));
    expect(warnings).not.toHaveBeenCalled();
    socket.close();
    Websocket.createSocket = (url) => new WebSocket(url);
    errors.mockRestore();
    warnings.mockRestore();
});
