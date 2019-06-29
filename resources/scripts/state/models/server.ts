import getServer, { Server } from '@/api/server/getServer';
import { action, Action, thunk, Thunk } from 'easy-peasy';
import socket, { SocketState } from './socket';

export interface ServerState {
    data?: Server;
    socket: SocketState;
    getServer: Thunk<ServerState, string, {}, any, Promise<void>>;
    setServer: Action<ServerState, Server>;
    clearServerState: Action<ServerState>;
}

const server: ServerState = {
    socket,
    getServer: thunk(async (actions, payload) => {
        const server = await getServer(payload);
        actions.setServer(server);
    }),
    setServer: action((state, payload) => {
        state.data = payload;
    }),
    clearServerState: action(state => {
        state.data = undefined;

        if (state.socket.instance) {
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
};

export default server;
