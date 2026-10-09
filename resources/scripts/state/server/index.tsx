import React, { createContext, use, useMemo, useState } from 'react';
import { useLocation } from '@tanstack/react-router';
import { useStore } from 'zustand';
import type { ServerSet } from '@/state/server/types';
import { createStore } from 'zustand/vanilla';
import { createSocket, type SocketStore } from '@/state/server/socket';
import { createFiles, type ServerFileStore } from '@/state/server/files';
import { hashToPath } from '@/helpers';

export type ServerStatus = 'offline' | 'starting' | 'stopping' | 'running' | null;

interface ServerStatusStore {
    value: ServerStatus;
    setServerStatus: (payload: ServerStatus) => void;
}

const createStatus = (set: ServerSet): ServerStatusStore => ({
    value: null,
    setServerStatus: (payload) =>
        set((state) => ({
            status: {
                ...state.status,
                value: payload,
            },
        })),
});

export interface ServerStore {
    files: ServerFileStore;
    socket: SocketStore;
    status: ServerStatusStore;
    clearServerState: () => void;
}

const createServerStore = () =>
    createStore<ServerStore>()((set, get) => ({
        socket: createSocket(set),
        status: createStatus(set),
        files: createFiles(set, get),

        clearServerState: () => {
            const sock = get().socket.instance;

            if (sock) {
                sock.removeAllListeners();
                sock.close();
            }

            set((state) => ({
                files: {
                    ...state.files,
                    selectedDirectory: null,
                    selectedFiles: [],
                },
                socket: {
                    ...state.socket,
                    instance: null,
                    connected: false,
                    reconnecting: false,
                },
                status: {
                    ...state.status,
                    value: null,
                },
            }));
        },
    }));

type ServerStoreApi = ReturnType<typeof createServerStore>;

const StoreContext = createContext<ServerStoreApi | null>(null);
const EMPTY_SELECTED_FILES: string[] = [];

export const Provider = ({ children }: { children?: React.ReactNode }) => {
    const [store] = useState(createServerStore);

    return <StoreContext.Provider value={store}>{children}</StoreContext.Provider>;
};

const useServerStoreApi = (): ServerStoreApi => {
    const store = use(StoreContext);

    if (!store) {
        throw new Error('ServerContext.Provider is missing from the component tree.');
    }

    return store;
};

export function useServerStore<R>(selector: (state: ServerStore) => R): R {
    return useStore(useServerStoreApi(), selector);
}

export const useServerDirectory = () => {
    const hash = useLocation({ select: (location) => location.hash });

    return hashToPath(hash);
};

export const useSelectedFiles = () => {
    const directory = useServerDirectory();

    return useServerStore((state) =>
        state.files.selectedDirectory === directory ? state.files.selectedFiles : EMPTY_SELECTED_FILES
    );
};

export const useServerStatus = () => useServerStore((state) => state.status.value);
export const useSocketConnected = () => useServerStore((state) => state.socket.connected);
export const useSocketInstance = () => useServerStore((state) => state.socket.instance);
export const useFileUploadActions = () => {
    const removeFileUpload = useServerStore((state) => state.files.removeFileUpload);
    const pushFileUpload = useServerStore((state) => state.files.pushFileUpload);
    const setUploadProgress = useServerStore((state) => state.files.setUploadProgress);

    return useMemo(
        () => ({ removeFileUpload, pushFileUpload, setUploadProgress }),
        [pushFileUpload, removeFileUpload, setUploadProgress]
    );
};
