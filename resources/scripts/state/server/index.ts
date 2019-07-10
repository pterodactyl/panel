import getServer, { Server } from '@/api/server/getServer';
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy';
import socket, { SocketStore } from './socket';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running';

interface ServerDataStore {
    data?: Server;
    getServer: Thunk<ServerDataStore, string, {}, any, Promise<void>>;
    setServer: Action<ServerDataStore, Server>;
}

const server: ServerDataStore = {
    getServer: thunk(async (actions, payload) => {
        const server = await getServer(payload);
        actions.setServer(server);
    }),
    setServer: action((state, payload) => {
        state.data = payload;
    }),
};

interface ServerStatusStore {
    value: ServerStatus;
    setServerStatus: Action<ServerStatusStore, ServerStatus>;
}

const status: ServerStatusStore = {
    value: 'offline',
    setServerStatus: action((state, payload) => {
        state.value = payload;
    }),
};

export interface ServerStore {
    server: ServerDataStore;
    socket: SocketStore;
    status: ServerStatusStore;
    clearServerState: Action<ServerStore>;
}

export const ServerContext = createContextStore<ServerStore>({
    server,
    socket,
    status,
    clearServerState: action(state => {
        state.server.data = undefined;

        if (state.socket.instance) {
            state.socket.instance.removeAllListeners();
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
}, { name: 'ServerStore' });
