import io from 'socket.io-client';
import camelCase from 'camelcase';
import SocketEmitter from './emitter';

const SYSTEM_EVENTS = [
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
    constructor (store = null) {
        this.socket = null;
        this.store = store;
    }

    /**
     * Initialize a new Socket connection.
     *
     * @param {io} socket
     */
    connect (socket) {
        if (!socket instanceof io) {
            throw new Error('First argument passed to connect() should be an instance of socket.io-client.');
        }

        this.socket = socket;
        this.registerEventListeners();
    }

    /**
     * Return the socket instance we are working with.
     *
     * @return {io|null}
     */
    instance () {
        return this.socket;
    }

    /**
     * Register the event listeners for this socket including user-defined ones in the store as
     * well as global system events from Socekt.io.
     */
    registerEventListeners () {
        this.socket['onevent'] = (packet) => {
            const [event, ...args] = packet.data;
            SocketEmitter.emit(event, ...args);
            this.passToStore(event, args);
        };

        SYSTEM_EVENTS.forEach((event) => {
            this.socket.on(event, (payload) => {
                SocketEmitter.emit(event, payload);
                this.passToStore(event, payload);
            })
        });
    }

    /**
     * Pass event calls off to the Vuex store if there is a corresponding function.
     *
     * @param {String|Number|Symbol} event
     * @param {Array} payload
     */
    passToStore (event, payload) {
        if (!this.store) {
            return;
        }

        const mutation = `SOCKET_${event.toUpperCase()}`;
        const action = `socket_${camelCase(event)}`;

        Object.keys(this.store._mutations).filter((namespaced) => {
            return namespaced.split('/').pop() === mutation;
        }).forEach((namespaced) => {
            this.store.commit(namespaced, this.unwrap(payload));
        });

        Object.keys(this.store._actions).filter((namespaced) => {
            return namespaced.split('/').pop() === action;
        }).forEach((namespaced) => {
            this.store.dispatch(namespaced, this.unwrap(payload));
        });
    }

    /**
     * @param {Array} args
     * @return {Array<Object>|Object}
     */
    unwrap (args) {
        return (args && args.length <= 1) ? args[0] : args;
    }
}
