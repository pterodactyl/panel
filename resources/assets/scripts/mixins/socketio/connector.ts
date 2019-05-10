import {camelCase} from 'lodash';
import SocketEmitter from './emitter';
import {Store} from "vuex";

const SOCKET_CONNECT = 'connect';
const SOCKET_ERROR = 'error';
const SOCKET_DISCONNECT = 'disconnect';

// This is defined in the wings daemon code and referenced here so that it is obvious
// where we are pulling these random data objects from.
type WingsWebsocketResponse = {
    event: string,
    args: Array<string>
}

export default class SocketioConnector {
    /**
     * The socket instance.
     */
    socket: null | WebSocket;

    /**
     * The vuex store being used to persist data and socket state.
     */
    store: Store<any> | undefined;

    /**
     * Tracks a reconnect attempt for the websocket. Will gradually back off on attempts
     * after a certain period of time has elapsed.
     */
    private reconnectTimeout: any;

    /**
     * Tracks the number of reconnect attempts which is used to determine the backoff
     * throttle for connections.
     */
    private reconnectAttempts: number = 0;

    private socketProtocol?: string;
    private socketUrl?: string;

    constructor(store: Store<any> | undefined) {
        this.socket = null;
        this.store = store;
    }

    /**
     * Initialize a new Socket connection.
     */
    public connect(url: string, protocol?: string): void {
        this.socketUrl = url;
        this.socketProtocol = protocol;

        this.connectToSocket()
            .then(socket => {
                this.socket = socket;
                this.emitAndPassToStore(SOCKET_CONNECT);
                this.registerEventListeners();
            })
            .catch(() => this.reconnectToSocket());
    }

    /**
     * Return the socket instance we are working with.
     */
    public instance(): WebSocket | null {
        return this.socket;
    }

    /**
     * Sends an event along to the websocket. If there is no active connection, a void
     * result is returned.
     */
    public emit(event: string, payload?: string | Array<string>): void | false {
        if (!this.socket) {
            return false
        }

        this.socket.send(JSON.stringify({
            event, args: typeof payload === 'string' ? [payload] : payload
        }));
    }

    /**
     * Register the event listeners for this socket including user-defined ones in the store as
     * well as global system events from Socekt.io.
     */
    protected registerEventListeners() {
        if (!this.socket) {
            return;
        }

        this.socket.onclose = () => {
            this.reconnectToSocket();
            this.emitAndPassToStore(SOCKET_DISCONNECT);
        };

        this.socket.onerror = () => {
            if (this.socket && this.socket.readyState !== WebSocket.OPEN) {
                this.emitAndPassToStore(SOCKET_ERROR, ['Failed to connect to websocket.']);
            }
        };

        this.socket.onmessage = (wse): void => {
            try {
                let {event, args}: WingsWebsocketResponse = JSON.parse(wse.data);

                this.emitAndPassToStore(event, args);
            } catch (ex) {
                // do nothing, bad JSON response
                console.error(ex);
                return
            }
        };
    }

    /**
     * Performs an actual socket connection, wrapped as a Promise for an easier interface.
     */
    protected connectToSocket(): Promise<WebSocket> {
        return new Promise((resolve, reject) => {
            let hasReturned = false;
            const socket = new WebSocket(this.socketUrl!, this.socketProtocol);

            socket.onopen = () => {
                if (hasReturned) {
                    socket && socket.close();
                }

                hasReturned = true;
                this.resetConnectionAttempts();
                resolve(socket);
            };

            const rejectFunc = () => {
                if (!hasReturned) {
                    hasReturned = true;
                    this.emitAndPassToStore(SOCKET_ERROR, ['Failed to connect to websocket.']);
                    reject();
                }
            };

            socket.onerror = rejectFunc;
            socket.onclose = rejectFunc;
        });
    }


    /**
     * Attempts to reconnect to the socket instance if it becomes disconnected.
     */
    private reconnectToSocket() {
        const { socket } = this;
        if (!socket) {
            return;
        }

        // Clear the existing timeout if one exists for some reason.
        this.reconnectTimeout && clearTimeout(this.reconnectTimeout);

        this.reconnectTimeout = setTimeout(() => {
            console.warn(`Attempting to reconnect to websocket [${this.reconnectAttempts}]...`);

            this.reconnectAttempts++;
            this.connect(this.socketUrl!, this.socketProtocol);
        }, this.getIntervalTimeout());
    }

    private resetConnectionAttempts() {
        this.reconnectTimeout && clearTimeout(this.reconnectTimeout);
        this.reconnectAttempts = 0;
    }

    /**
     * Determine the amount of time we should wait before attempting to reconnect to the socket.
     */
    private getIntervalTimeout(): number {
        if (this.reconnectAttempts < 10) {
            return 50;
        } else if (this.reconnectAttempts < 25) {
            return 500;
        } else if (this.reconnectAttempts < 50) {
            return 1000;
        }

        return 2500;
    }


    /**
     * Emits the event over the event emitter and also passes it along to the vuex store.
     */
    private emitAndPassToStore(event: string, payload?: Array<string>) {
        payload ? SocketEmitter.emit(event, ...payload) : SocketEmitter.emit(event);
        this.passToStore(event, payload);
    }

    /**
     * Pass event calls off to the Vuex store if there is a corresponding function.
     */
    private passToStore(event: string, payload?: Array<string>) {
        if (!this.store) {
            return;
        }

        const s: Store<any> = this.store;
        const mutation = `SOCKET_${event.toUpperCase()}`;
        const action = `socket_${camelCase(event)}`;

        // @ts-ignore
        Object.keys(this.store._mutations).filter((namespaced: string): boolean => {
            return namespaced.split('/').pop() === mutation;
        }).forEach((namespaced: string): void => {
            s.commit(namespaced, payload ? this.unwrap(payload) : null);
        });

        // @ts-ignore
        Object.keys(this.store._actions).filter((namespaced: string): boolean => {
            return namespaced.split('/').pop() === action;
        }).forEach((namespaced: string): void => {
            s.dispatch(namespaced, payload ? this.unwrap(payload) : null).catch(console.error);
        });
    }

    private unwrap(args: Array<string>) {
        return (args && args.length <= 1) ? args[0] : args;
    }
}
