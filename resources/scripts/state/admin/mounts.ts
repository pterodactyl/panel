import { action, Action } from 'easy-peasy';

export interface AdminMountStore {
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

export default mounts;
