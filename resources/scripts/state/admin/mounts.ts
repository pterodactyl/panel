import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminMountStore {
    selectedMounts: number[];

    setSelectedMounts: Action<AdminMountStore, number[]>;
    appendSelectedMount: Action<AdminMountStore, number>;
    removeSelectedMount: Action<AdminMountStore, number>;
}

const mounts: AdminMountStore = {
    selectedMounts: [],

    setSelectedMounts: action((state, payload) => {
        state.selectedMounts = payload;
    }),

    appendSelectedMount: action((state, payload) => {
        state.selectedMounts = state.selectedMounts.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedMount: action((state, payload) => {
        state.selectedMounts = state.selectedMounts.filter(id => id !== payload);
    }),
};

export type { AdminMountStore };
export default mounts;
