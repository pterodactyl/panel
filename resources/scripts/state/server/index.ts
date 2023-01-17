import type { Action, Computed, Thunk } from 'easy-peasy';
import { action, computed, createContextStore, thunk } from 'easy-peasy';
import isEqual from 'react-fast-compare';

import type { Server } from '@/api/server/getServer';
import getServer from '@/api/server/getServer';
import type { ServerDatabaseStore } from '@/state/server/databases';
import databases from '@/state/server/databases';
import type { ServerFileStore } from '@/state/server/files';
import files from '@/state/server/files';
import type { ServerScheduleStore } from '@/state/server/schedules';
import schedules from '@/state/server/schedules';
import type { SocketStore } from '@/state/server/socket';
import socket from '@/state/server/socket';
import type { ServerSubuserStore } from '@/state/server/subusers';
import subusers from '@/state/server/subusers';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running' | null;

interface ServerDataStore {
    data?: Server;
    inConflictState: Computed<ServerDataStore, boolean>;
    isInstalling: Computed<ServerDataStore, boolean>;
    permissions: string[];

    getServer: Thunk<ServerDataStore, string, Record<string, unknown>, ServerStore, Promise<void>>;
    setServer: Action<ServerDataStore, Server>;
    setServerFromState: Action<ServerDataStore, (s: Server) => Server>;
    setPermissions: Action<ServerDataStore, string[]>;
}

const server: ServerDataStore = {
    permissions: [],

    inConflictState: computed(state => {
        if (!state.data) {
            return false;
        }

        return state.data.status !== null || state.data.isTransferring || state.data.isNodeUnderMaintenance;
    }),

    isInstalling: computed(state => {
        return state.data?.status === 'installing' || state.data?.status === 'install_failed';
    }),

    getServer: thunk(async (actions, payload) => {
        const [server, permissions] = await getServer(payload);

        actions.setServer(server);
        actions.setPermissions(permissions);
    }),

    setServer: action((state, payload) => {
        if (!isEqual(payload, state.data)) {
            state.data = payload;
        }
    }),

    setServerFromState: action((state, payload) => {
        const output = payload(state.data!);
        if (!isEqual(output, state.data)) {
            state.data = output;
        }
    }),

    setPermissions: action((state, payload) => {
        if (!isEqual(payload, state.permissions)) {
            state.permissions = payload;
        }
    }),
};

interface ServerStatusStore {
    value: ServerStatus;
    setServerStatus: Action<ServerStatusStore, ServerStatus>;
}

const status: ServerStatusStore = {
    value: null,
    setServerStatus: action((state, payload) => {
        state.value = payload;
    }),
};

export interface ServerStore {
    server: ServerDataStore;
    subusers: ServerSubuserStore;
    databases: ServerDatabaseStore;
    files: ServerFileStore;
    schedules: ServerScheduleStore;
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
    schedules,
    clearServerState: action(state => {
        state.server.data = undefined;
        state.server.permissions = [];
        state.databases.data = [];
        state.subusers.data = [];
        state.files.directory = '/';
        state.files.selectedFiles = [];
        state.schedules.data = [];

        if (state.socket.instance) {
            state.socket.instance.removeAllListeners();
            state.socket.instance.close();
        }

        state.socket.instance = null;
        state.socket.connected = false;
    }),
});
