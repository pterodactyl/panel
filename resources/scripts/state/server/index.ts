import getServer, { Server } from '@/api/server/getServer';
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy';
import socket, { SocketStore } from './socket';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import files, { ServerFileStore } from '@/state/server/files';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running';

interface ServerDataStore {
    data?: Server;
    getServer: Thunk<ServerDataStore, string, {}, ServerStore, Promise<void>>;
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

interface ServerDatabaseStore {
    items: ServerDatabase[];
    setDatabases: Action<ServerDatabaseStore, ServerDatabase[]>;
    appendDatabase: Action<ServerDatabaseStore, ServerDatabase>;
    removeDatabase: Action<ServerDatabaseStore, ServerDatabase>;
}

const databases: ServerDatabaseStore = {
    items: [],
    setDatabases: action((state, payload) => {
        state.items = payload;
    }),
    appendDatabase: action((state, payload) => {
        state.items = state.items.filter(item => item.id !== payload.id).concat(payload);
    }),
    removeDatabase: action((state, payload) => {
        state.items = state.items.filter(item => item.id !== payload.id);
    }),
};

export interface ServerStore {
    server: ServerDataStore;
    databases: ServerDatabaseStore;
    files: ServerFileStore;
    socket: SocketStore;
    status: ServerStatusStore;
    clearServerState: Action<ServerStore>;
}

export const ServerContext = createContextStore<ServerStore>({
    server,
    socket,
    status,
    databases,
    files,
    clearServerState: action(state => {
        state.server.data = undefined;
        state.databases.items = [];

        if (state.socket.instance) {
            state.socket.instance.removeAllListeners();
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
}, { name: 'ServerStore' });
