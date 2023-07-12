import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminNodeStore {
    selectedNodes: number[];

    setSelectedNodes: Action<AdminNodeStore, number[]>;
    appendSelectedNode: Action<AdminNodeStore, number>;
    removeSelectedNode: Action<AdminNodeStore, number>;
}

const nodes: AdminNodeStore = {
    selectedNodes: [],

    setSelectedNodes: action((state, payload) => {
        state.selectedNodes = payload;
    }),

    appendSelectedNode: action((state, payload) => {
        state.selectedNodes = state.selectedNodes.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedNode: action((state, payload) => {
        state.selectedNodes = state.selectedNodes.filter(id => id !== payload);
    }),
};

export type { AdminNodeStore };
export default nodes;
