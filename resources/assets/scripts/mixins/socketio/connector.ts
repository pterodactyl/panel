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

    constructor(store: Store<any> | undefined) {
        this.socket = null;
        this.store = store;
    }

    /**
     * Initialize a new Socket connection.
     */
    connect(url: string, protocols?: string | string[]): void {
        this.socket = new WebSocket(url, protocols);
        this.registerEventListeners();
    }

    /**
     * Return the socket instance we are working with.
     */
    instance(): WebSocket | null {
        return this.socket;
    }

    /**
     * Sends an event along to the websocket. If there is no active connection, a void
     * result is returned.
     */
    emit(event: string, payload?: string | Array<string>): void | false {
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
    registerEventListeners() {
        if (!this.socket) {
            return;
        }

        this.socket.onopen = () => this.emitAndPassToStore(SOCKET_CONNECT);
        this.socket.onclose = () => this.emitAndPassToStore(SOCKET_DISCONNECT);
        this.socket.onerror = () => {
            // @todo reconnect?
            if (this.socket && this.socket.readyState !== 1) {
                this.emitAndPassToStore(SOCKET_ERROR, ['Failed to connect to websocket.']);
            }
        };

        this.socket.onmessage = (wse): void => {
            console.log('Socket message:', wse.data);

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
     * Emits the event over the event emitter and also passes it along to the vuex store.
     */
    emitAndPassToStore(event: string, payload?: Array<string>) {
        payload ? SocketEmitter.emit(event, ...payload) : SocketEmitter.emit(event);
        this.passToStore(event, payload);
    }

    /**
     * Pass event calls off to the Vuex store if there is a corresponding function.
     */
    passToStore(event: string, payload?: Array<string>) {
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

    unwrap(args: Array<string>) {
        return (args && args.length <= 1) ? args[0] : args;
    }
}
