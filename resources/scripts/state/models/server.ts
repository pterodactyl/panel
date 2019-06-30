import getServer, { Server } from '@/api/server/getServer';
import { action, Action, thunk, Thunk } from 'easy-peasy';
import socket, { SocketState } from './socket';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running';

export interface ServerState {
    data?: Server;
    status: ServerStatus;
    socket: SocketState;
    getServer: Thunk<ServerState, string, {}, any, Promise<void>>;
    setServer: Action<ServerState, Server>;
    setServerStatus: Action<ServerState, ServerStatus>;
    clearServerState: Action<ServerState>;
}

const server: ServerState = {
    socket,
    status: 'offline',
    getServer: thunk(async (actions, payload) => {
        const server = await getServer(payload);
        actions.setServer(server);
    }),
    setServer: action((state, payload) => {
        state.data = payload;
    }),
    setServerStatus: action((state, payload) => {
        state.status = payload;
    }),
    clearServerState: action(state => {
        state.data = undefined;

        if (state.socket.instance) {
            state.socket.instance.removeAllListeners();
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
};

export default server;
