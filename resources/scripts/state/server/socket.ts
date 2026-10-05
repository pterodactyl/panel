import type { ServerSet } from '@/state/server/types';
import type { Websocket } from '@/plugins/Websocket';

export interface SocketStore {
    instance: Websocket | null;
    connected: boolean;
    reconnecting: boolean;
    setInstance: (payload: Websocket | null) => void;
    setConnectionState: (payload: boolean) => void;
    setReconnecting: (payload: boolean) => void;
}

export const createSocket = (set: ServerSet): SocketStore => ({
    instance: null,
    connected: false,
    reconnecting: false,
    setInstance: (payload) =>
        set((state) => ({
            socket: {
                ...state.socket,
                instance: payload,
            },
        })),
    setConnectionState: (payload) =>
        set((state) => ({
            socket: {
                ...state.socket,
                connected: payload,
            },
        })),
    setReconnecting: (payload) =>
        set((state) => ({
            socket: {
                ...state.socket,
                reconnecting: payload,
            },
        })),
});
