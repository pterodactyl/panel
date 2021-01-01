import { action, Action } from 'easy-peasy';
import { Nest } from '@/api/admin/nests/getNests';

export interface AdminNestStore {
    data: Nest[];
    selectedNests: number[];

    setNests: Action<AdminNestStore, Nest[]>;
    appendNest: Action<AdminNestStore, Nest>;
    removeNest: Action<AdminNestStore, number>;

    setSelectedNests: Action<AdminNestStore, number[]>;
    appendSelectedNest: Action<AdminNestStore, number>;
    removeSelectedNest: Action<AdminNestStore, number>;
}

const nests: AdminNestStore = {
    data: [],
    selectedNests: [],

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

    setSelectedNests: action((state, payload) => {
        state.selectedNests = payload;
    }),

    appendSelectedNest: action((state, payload) => {
        if (state.selectedNests.find(id => id === payload)) {
            state.selectedNests = state.selectedNests.map(id => id === payload ? payload : id);
        } else {
            state.selectedNests = [ ...state.selectedNests, payload ];
        }
    }),

    removeSelectedNest: action((state, payload) => {
        state.selectedNests = [ ...state.selectedNests.filter(id => id !== payload) ];
    }),
};

export default nests;
