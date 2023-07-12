import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminAllocationStore {
    selectedAllocations: number[];

    setSelectedAllocations: Action<AdminAllocationStore, number[]>;
    appendSelectedAllocation: Action<AdminAllocationStore, number>;
    removeSelectedAllocation: Action<AdminAllocationStore, number>;
}

const allocations: AdminAllocationStore = {
    selectedAllocations: [],

    setSelectedAllocations: action((state, payload) => {
        state.selectedAllocations = payload;
    }),

    appendSelectedAllocation: action((state, payload) => {
        state.selectedAllocations = state.selectedAllocations.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedAllocation: action((state, payload) => {
        state.selectedAllocations = state.selectedAllocations.filter(id => id !== payload);
    }),
};

export type { AdminAllocationStore };
export default allocations;
