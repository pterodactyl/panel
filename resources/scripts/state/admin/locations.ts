import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminLocationStore {
    selectedLocations: number[];

    setSelectedLocations: Action<AdminLocationStore, number[]>;
    appendSelectedLocation: Action<AdminLocationStore, number>;
    removeSelectedLocation: Action<AdminLocationStore, number>;
}

const locations: AdminLocationStore = {
    selectedLocations: [],

    setSelectedLocations: action((state, payload) => {
        state.selectedLocations = payload;
    }),

    appendSelectedLocation: action((state, payload) => {
        state.selectedLocations = state.selectedLocations.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedLocation: action((state, payload) => {
        state.selectedLocations = state.selectedLocations.filter(id => id !== payload);
    }),
};

export type { AdminLocationStore };
export default locations;
