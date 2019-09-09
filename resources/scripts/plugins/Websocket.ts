import Sockette from 'sockette';
import getWebsocketToken from '@/api/server/getWebsocketToken';
import { EventEmitter } from 'events';

export const SOCKET_EVENTS = [
    'SOCKET_OPEN',
    'SOCKET_RECONNECT',
    'SOCKET_CLOSE',
    'SOCKET_ERROR',
];

export class Websocket extends EventEmitter {
    private socket: Sockette | null;
    private readonly uuid: string;

    constructor (uuid: string) {
        super();

        this.socket = null;
        this.uuid = uuid;
    }

    async connect (): Promise<void> {
        getWebsocketToken(this.uuid)
            .then(url => {
                this.socket = new Sockette(url, {
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

                return Promise.resolve();
            })
            .catch(error => Promise.reject(error));
    }

    close (code?: number, reason?: string) {
        this.socket && this.socket.close(code, reason);
    }

    open () {
        this.socket && this.socket.open();
    }

    reconnect () {
        this.socket && this.socket.reconnect();
    }

    send (event: string, payload?: string | string[]) {
        this.socket && this.socket.send(JSON.stringify({
            event, args: Array.isArray(payload) ? payload : [ payload ],
        }));
    }
}
