import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminServerStore {
    selectedServers: number[];

    setSelectedServers: Action<AdminServerStore, number[]>;
    appendSelectedServer: Action<AdminServerStore, number>;
    removeSelectedServer: Action<AdminServerStore, number>;
}

const servers: AdminServerStore = {
    selectedServers: [],

    setSelectedServers: action((state, payload) => {
        state.selectedServers = payload;
    }),

    appendSelectedServer: action((state, payload) => {
        state.selectedServers = state.selectedServers.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedServer: action((state, payload) => {
        state.selectedServers = state.selectedServers.filter(id => id !== payload);
    }),
};

export type { AdminServerStore };
export default servers;
