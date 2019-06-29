import { Action, action } from 'easy-peasy';
import Sockette from 'sockette';

export interface SocketState {
    instance: Sockette | null;
    connected: boolean;
    setInstance: Action<SocketState, Sockette | null>;
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
