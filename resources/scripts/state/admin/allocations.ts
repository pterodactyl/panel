import { action, Action } from 'easy-peasy';

export interface AdminAllocationStore {
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

export default allocations;
