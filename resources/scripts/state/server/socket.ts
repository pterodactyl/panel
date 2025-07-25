import { Action, action } from 'easy-peasy';
import { Websocket } from '@/plugins/Websocket';

export interface SocketStore {
    instance: Websocket | null;
    connected: boolean;
    setInstance: Action<SocketStore, Websocket | null>;
    setConnectionState: Action<SocketStore, boolean>;
}

const socket: SocketStore = {
    instance: null,
    connected: false,
    setInstance: action((state, payload) => {
        state.instance = payload;
    }),
    setConnectionState: action((state, payload) => {
        state.connected = payload;
    }),
};

export default socket;
