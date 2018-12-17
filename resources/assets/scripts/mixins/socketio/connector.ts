import * as io from 'socket.io-client';
import {camelCase} from 'lodash';
import SocketEmitter from './emitter';
import {Store} from "vuex";

const SYSTEM_EVENTS: Array<string> = [
    'connect',
    'error',
    'disconnect',
    'reconnect',
    'reconnect_attempt',
    'reconnecting',
    'reconnect_error',
    'reconnect_failed',
    'connect_error',
    'connect_timeout',
    'connecting',
    'ping',
    'pong',
];

export default class SocketioConnector {
    /**
     * The socket instance.
     */
    socket: null | SocketIOClient.Socket;

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
     *
     * @param {io} socket
     */
    connect(socket: SocketIOClient.Socket) {
        this.socket = socket;
        this.registerEventListeners();
    }

    /**
     * Return the socket instance we are working with.
     */
    instance(): SocketIOClient.Socket | null {
        return this.socket;
    }

    /**
     * Register the event listeners for this socket including user-defined ones in the store as
     * well as global system events from Socekt.io.
     */
    registerEventListeners() {
        if (!this.socket) {
            return;
        }

        // @ts-ignore
        this.socket['onevent'] = (packet: { data: Array<any> }): void => {
            const [event, ...args] = packet.data;
            SocketEmitter.emit(event, ...args);

            this.passToStore(event, args);
        };

        SYSTEM_EVENTS.forEach((event: string): void => {
            if (!this.socket) {
                return;
            }

            this.socket.on(event, (payload: any) => {
                SocketEmitter.emit(event, payload);

                this.passToStore(event, payload);
            });
        });
    }

    /**
     * Pass event calls off to the Vuex store if there is a corresponding function.
     */
    passToStore(event: string | number, payload: Array<any>) {
        if (!this.store) {
            return;
        }

        const s: Store<any> = this.store;
        const mutation = `SOCKET_${String(event).toUpperCase()}`;
        const action = `socket_${camelCase(String(event))}`;

        // @ts-ignore
        Object.keys(this.store._mutations).filter((namespaced: string): boolean => {
            return namespaced.split('/').pop() === mutation;
        }).forEach((namespaced: string): void => {
            s.commit(namespaced, this.unwrap(payload));
        });

        // @ts-ignore
        Object.keys(this.store._actions).filter((namespaced: string): boolean => {
            return namespaced.split('/').pop() === action;
        }).forEach((namespaced: string): void => {
            s.dispatch(namespaced, this.unwrap(payload)).catch(console.error);
        });
    }

    unwrap(args: Array<any>) {
        return (args && args.length <= 1) ? args[0] : args;
    }
}
