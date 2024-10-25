import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminNestStore {
    selectedNests: number[];

    setSelectedNests: Action<AdminNestStore, number[]>;
    appendSelectedNest: Action<AdminNestStore, number>;
    removeSelectedNest: Action<AdminNestStore, number>;
}

const nests: AdminNestStore = {
    selectedNests: [],

    setSelectedNests: action((state, payload) => {
        state.selectedNests = payload;
    }),

    appendSelectedNest: action((state, payload) => {
        state.selectedNests = state.selectedNests.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedNest: action((state, payload) => {
        state.selectedNests = state.selectedNests.filter(id => id !== payload);
    }),
};

export type { AdminNestStore };
export default nests;
