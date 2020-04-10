import getServer, { Server } from '@/api/server/getServer';
import { action, Action, createContextStore, thunk, Thunk } from 'easy-peasy';
import socket, { SocketStore } from './socket';
import { ServerDatabase } from '@/api/server/getServerDatabases';
import files, { ServerFileStore } from '@/state/server/files';
import subusers, { ServerSubuserStore } from '@/state/server/subusers';
import { composeWithDevTools } from 'redux-devtools-extension';
import backups, { ServerBackupStore } from '@/state/server/backups';
import schedules, { ServerScheduleStore } from '@/state/server/schedules';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running';

interface ServerDataStore {
    data?: Server;
    permissions: string[];

    getServer: Thunk<ServerDataStore, string, {}, ServerStore, Promise<void>>;
    setServer: Action<ServerDataStore, Server>;
    setPermissions: Action<ServerDataStore, string[]>;
}

const server: ServerDataStore = {
    permissions: [],

    getServer: thunk(async (actions, payload) => {
        const [server, permissions] = await getServer(payload);

        actions.setServer(server);
        actions.setPermissions(permissions);
    }),

    setServer: action((state, payload) => {
        state.data = payload;
    }),

    setPermissions: action((state, payload) => {
        state.permissions = payload;
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
    subusers: ServerSubuserStore;
    databases: ServerDatabaseStore;
    files: ServerFileStore;
    schedules: ServerScheduleStore;
    backups: ServerBackupStore;
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
    subusers,
    backups,
    schedules,
    clearServerState: action(state => {
        state.server.data = undefined;
        state.server.permissions = [];
        state.databases.items = [];
        state.subusers.data = [];
        state.files.directory = '/';
        state.files.contents = [];
        state.backups.data = [];
        state.schedules.data = [];

        if (state.socket.instance) {
            state.socket.instance.removeAllListeners();
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
}, {
    compose: composeWithDevTools({
        name: 'ServerStore',
        trace: true,
    }),
});
