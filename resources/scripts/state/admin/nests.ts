import { action, Action } from 'easy-peasy';
import { Nest } from '@/api/admin/nests/getNests';

export interface AdminNestStore {
    data: Nest[];
    setNests: Action<AdminNestStore, Nest[]>;
    appendNest: Action<AdminNestStore, Nest>;
    removeNest: Action<AdminNestStore, number>;
}

const nests: AdminNestStore = {
    data: [],

    setNests: action((state, payload) => {
        state.data = payload;
    }),

    appendNest: action((state, payload) => {
        if (state.data.find(nest => nest.id === payload.id)) {
            state.data = state.data.map(nest => nest.id === payload.id ? payload : nest);
        } else {
            state.data = [ ...state.data, payload ];
        }
    }),

    removeNest: action((state, payload) => {
        state.data = [ ...state.data.filter(nest => nest.id !== payload) ];
    }),
};

export default nests;
