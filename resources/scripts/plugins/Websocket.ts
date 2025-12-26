import Sockette from 'sockette';
import { EventEmitter } from 'events';

export class Websocket extends EventEmitter {
    // The socket instance being tracked.
    private socket: Sockette | null = null;

    // The URL being connected to for the socket.
    private url: string | null = null;

    // The authentication token passed along with every request to the Daemon.
    // By default this token expires every 15 minutes and must therefore be
    // refreshed at a pretty continuous interval. The socket server will respond
    // with "token expiring" and "token expired" events when approaching 3 minutes
    // and 0 minutes to expiry.
    private token = '';

    // Connects to the websocket instance and sets the token for the initial request.
    connect(url: string): this {
        this.url = url;

        this.socket = new Sockette(`${this.url}`, {
            timeout: 1000,
            maxAttempts: 20,
            onmessage: (e) => {
                try {
                    const { event, args } = JSON.parse(e.data);
                    args ? this.emit(event, ...args) : this.emit(event);
                } catch (ex) {
                    console.warn('Failed to parse incoming websocket message.', ex);
                }
            },
            onopen: () => {
                this.emit('SOCKET_OPEN');
                this.authenticate();
            },
            onreconnect: (evt) => {
                // We return code 4409 from Wings when a server is suspended. We've
                // gone ahead and reserved 4400 as well here for future expansion without
                // having to loop back around.
                //
                // If either of those codes is returned go ahead and abort here. Unfortunately
                // the underlying sockette logic always calls reconnect for any code that isn't
                // 1000/1001/1003, which is painful but we can just stop the flow here.
                // @ts-expect-error code is actually present here.
                if (evt.code === 4409 || evt.code === 4400) {
                    this.close(1000);
                } else {
                    this.emit('SOCKET_RECONNECT');
                }
            },
            onclose: () => this.emit('SOCKET_CLOSE'),
            onerror: (error) => this.emit('SOCKET_ERROR', error),
            onmaximum: () => this.emit('SOCKET_CONNECT_ERROR'),
        });

        return this;
    }

    // Sets the authentication token to use when sending commands back and forth
    // between the websocket instance.
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
        this.socket?.close(code, reason);
    }

    open() {
        this.socket?.open();
    }

    reconnect() {
        this.socket?.reconnect();
    }

    send(event: string, payload?: string | string[]) {
        this.socket?.json({ event, args: Array.isArray(payload) ? payload : [payload] });
    }
}
