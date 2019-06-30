import Sockette from 'sockette';
import { EventEmitter } from 'events';

export const SOCKET_EVENTS = [
    'SOCKET_OPEN',
    'SOCKET_RECONNECT',
    'SOCKET_CLOSE',
    'SOCKET_ERROR',
];

export class Websocket extends EventEmitter {
    socket: Sockette;

    constructor (url: string, protocol: string) {
        super();

        this.socket = new Sockette(url, {
            protocols: protocol,
            onmessage: e => {
                try {
                    let { event, args } = JSON.parse(e.data);
                    this.emit(event, ...args);
                } catch (ex) {
                    console.warn('Failed to parse incoming websocket message.', ex);
                }
            },
            onopen: () => this.emit('SOCKET_OPEN'),
            onreconnect: () => this.emit('SOCKET_RECONNECT'),
            onclose: () => this.emit('SOCKET_CLOSE'),
            onerror: () => this.emit('SOCKET_ERROR'),
        });
    }

    close (code?: number, reason?: string) {
        this.socket.close(code, reason);
    }

    open () {
        this.socket.open();
    }

    reconnect () {
        this.socket.reconnect();
    }

    send (event: string, payload?: string | string[]) {
        this.socket.send(JSON.stringify({
            event, args: Array.isArray(payload) ? payload : [ payload ],
        }));
    }
}
