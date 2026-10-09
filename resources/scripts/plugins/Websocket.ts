type Listener<TArgs extends unknown[] = unknown[]> = (...args: TArgs) => void;
type ManagedWebSocket = Pick<WebSocket, 'close' | 'send' | 'readyState'> & {
    onclose: ((event: CloseEvent) => void) | null;
    onerror: ((event: Event) => void) | null;
    onmessage: ((event: MessageEvent<string>) => void) | null;
    onopen: ((event: Event) => void) | null;
};

const NORMAL_CLOSE_CODES = new Set([1000, 1001, 1003]);
const SUSPENDED_CLOSE_CODES = new Set([4400, 4409]);
const SOCKET_OPEN_STATE = 1;
const AUTH_SUCCESS_EVENT = 'auth success';

export const RECONNECT_BASE_DELAY_MS = 1_000;
export const RECONNECT_MAX_DELAY_MS = 30_000;
export const MAX_RECONNECT_ATTEMPTS = 10;

/** Exponential backoff for a 1-based attempt, jittered within the upper half of the window. */
export const reconnectDelay = (attempt: number, random: () => number = Math.random): number => {
    const ceiling = Math.min(RECONNECT_MAX_DELAY_MS, RECONNECT_BASE_DELAY_MS * 2 ** Math.max(0, attempt - 1));

    return Math.round(ceiling / 2 + random() * (ceiling / 2));
};

const isOffline = () => !navigator.onLine;

class EventEmitter {
    private listenersByEvent = new Map<string, Set<Listener>>();

    on(event: string, listener: Listener<string[]>): this;
    on<TArgs extends unknown[]>(event: string, listener: Listener<TArgs>): this {
        let listeners = this.listenersByEvent.get(event);

        if (!listeners) {
            listeners = new Set();
            this.listenersByEvent.set(event, listeners);
        }

        listeners.add(listener as Listener);

        return this;
    }

    addListener(event: string, listener: Listener<string[]>): this;
    addListener<TArgs extends unknown[]>(event: string, listener: Listener<TArgs>): this {
        let listeners = this.listenersByEvent.get(event);

        if (!listeners) {
            listeners = new Set();
            this.listenersByEvent.set(event, listeners);
        }

        listeners.add(listener as Listener);

        return this;
    }

    removeListener(event: string, listener: Listener<string[]>): this;
    removeListener<TArgs extends unknown[]>(event: string, listener: Listener<TArgs>): this {
        this.listenersByEvent.get(event)?.delete(listener as Listener);

        return this;
    }

    removeAllListeners(event?: string): this {
        if (event === undefined) {
            this.listenersByEvent.clear();
        } else {
            this.listenersByEvent.delete(event);
        }

        return this;
    }

    emit<TArgs extends unknown[]>(event: string, ...args: TArgs): boolean {
        const listeners = this.listenersByEvent.get(event);

        if (!listeners || listeners.size === 0) {
            return false;
        }

        // oxlint-disable-next-line unicorn/no-useless-spread -- copied so listeners can unsubscribe mid-dispatch.
        for (const listener of [...listeners]) {
            try {
                (listener as Listener<TArgs>)(...args);
            } catch (error) {
                console.error(`Websocket listener failed for "${event}".`, error);
            }
        }

        return true;
    }
}

export class Websocket extends EventEmitter {
    static createSocket = (url: string): ManagedWebSocket => new WebSocket(url);

    private socket: ManagedWebSocket | null = null;

    private url: string | null = null;

    // Wings expires this token every 15 minutes.
    private token = '';

    private reconnectAttempts = 0;
    private reconnectTimer: ReturnType<typeof setTimeout> | null = null;

    private awaitingResume = false;

    connect(url: string): this {
        this.disposeSocket();
        this.url = url;
        this.reconnectAttempts = 0;
        this.clearReconnectTimer();
        this.stopAwaitingResume();
        this.openSocket();

        return this;
    }

    setToken(token: string, isUpdate = false): this {
        this.token = token;

        if (isUpdate) {
            this.authenticate();
        }

        return this;
    }

    authenticate() {
        if (this.url && this.token) {
            this.send('auth', this.token);
        }
    }

    close(code?: number, reason?: string) {
        this.url = null;
        this.token = '';
        this.clearReconnectTimer();
        this.stopAwaitingResume();
        this.disposeSocket(code, reason);
    }

    send(event: string, payload?: string | string[]): boolean {
        if (!this.socket || this.socket.readyState !== SOCKET_OPEN_STATE) {
            return false;
        }

        this.socket.send(JSON.stringify({ event, args: Array.isArray(payload) ? payload : [payload] }));

        return true;
    }

    private openSocket() {
        if (!this.url) {
            return;
        }

        const socket = Websocket.createSocket(this.url);

        this.socket = socket;

        socket.onmessage = (e) => {
            let message: { event: string; args?: unknown[] };

            try {
                message = JSON.parse(e.data);
            } catch (ex) {
                console.warn('Failed to parse incoming websocket message.', ex);

                return;
            }

            if (message.event === AUTH_SUCCESS_EVENT) {
                this.reconnectAttempts = 0;
            }

            if (message.args) {
                this.emit(message.event, ...message.args);
            } else {
                this.emit(message.event);
            }
        };

        socket.onopen = () => {
            this.emit('SOCKET_OPEN');
            this.authenticate();
        };

        socket.onerror = (error) => this.emit('SOCKET_ERROR', error);

        socket.onclose = (event) => {
            this.socket = null;
            this.emit('SOCKET_CLOSE');

            if (!this.url) {
                return;
            }

            // Wings closes with 4409 for suspended servers; 4400 is reserved.
            if (SUSPENDED_CLOSE_CODES.has(event.code)) {
                this.close(1000);

                return;
            }

            if (!NORMAL_CLOSE_CODES.has(event.code)) {
                this.scheduleReconnect();
            }
        };
    }

    private disposeSocket(code?: number, reason?: string) {
        const socket = this.socket;

        this.socket = null;

        if (socket) {
            socket.onopen = null;
            socket.onmessage = null;
            socket.onerror = null;
            socket.onclose = null;
            socket.close(code, reason);
        }
    }

    private scheduleReconnect() {
        if (!this.url || this.reconnectTimer || this.awaitingResume) {
            return;
        }

        if (isOffline()) {
            this.emit('SOCKET_RECONNECT');
            this.awaitResume();

            return;
        }

        if (this.reconnectAttempts >= MAX_RECONNECT_ATTEMPTS) {
            this.emit('SOCKET_CONNECT_ERROR');
            this.awaitResume();

            return;
        }

        this.reconnectAttempts += 1;
        this.emit('SOCKET_RECONNECT');
        this.reconnectTimer = setTimeout(() => {
            this.reconnectTimer = null;
            this.openSocket();
        }, reconnectDelay(this.reconnectAttempts));
    }

    private clearReconnectTimer() {
        if (this.reconnectTimer) {
            clearTimeout(this.reconnectTimer);
            this.reconnectTimer = null;
        }
    }

    private awaitResume() {
        if (this.awaitingResume) {
            return;
        }

        this.awaitingResume = true;
        window.addEventListener('online', this.resume);
        document.addEventListener('visibilitychange', this.resume);
    }

    private stopAwaitingResume() {
        if (!this.awaitingResume) {
            return;
        }

        this.awaitingResume = false;
        window.removeEventListener('online', this.resume);
        document.removeEventListener('visibilitychange', this.resume);
    }

    private readonly resume = () => {
        if (isOffline() || document.visibilityState === 'hidden') {
            return;
        }

        this.stopAwaitingResume();

        if (!this.url || this.socket) {
            return;
        }

        this.reconnectAttempts = 0;
        this.emit('SOCKET_RECONNECT');
        this.openSocket();
    };
}
