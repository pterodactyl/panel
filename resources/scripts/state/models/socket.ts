import { Action, action } from 'easy-peasy';
import { Websocket } from '@/plugins/Websocket';

export interface SocketState {
    instance: Websocket | null;
    connected: boolean;
    setInstance: Action<SocketState, Websocket | null>;
    setConnectionState: Action<SocketState, boolean>;
}

const socket: SocketState = {
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
