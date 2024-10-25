import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminDatabaseStore {
    selectedDatabases: number[];

    setSelectedDatabases: Action<AdminDatabaseStore, number[]>;
    appendSelectedDatabase: Action<AdminDatabaseStore, number>;
    removeSelectedDatabase: Action<AdminDatabaseStore, number>;
}

const databases: AdminDatabaseStore = {
    selectedDatabases: [],

    setSelectedDatabases: action((state, payload) => {
        state.selectedDatabases = payload;
    }),

    appendSelectedDatabase: action((state, payload) => {
        state.selectedDatabases = state.selectedDatabases.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedDatabase: action((state, payload) => {
        state.selectedDatabases = state.selectedDatabases.filter(id => id !== payload);
    }),
};

export type { AdminDatabaseStore };
export default databases;
