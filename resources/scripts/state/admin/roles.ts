import type { Action } from 'easy-peasy';
import { action } from 'easy-peasy';

interface AdminRoleStore {
    selectedRoles: number[];

    setSelectedRoles: Action<AdminRoleStore, number[]>;
    appendSelectedRole: Action<AdminRoleStore, number>;
    removeSelectedRole: Action<AdminRoleStore, number>;
}

const roles: AdminRoleStore = {
    selectedRoles: [],

    setSelectedRoles: action((state, payload) => {
        state.selectedRoles = payload;
    }),

    appendSelectedRole: action((state, payload) => {
        state.selectedRoles = state.selectedRoles.filter(id => id !== payload).concat(payload);
    }),

    removeSelectedRole: action((state, payload) => {
        state.selectedRoles = state.selectedRoles.filter(id => id !== payload);
    }),
};

export type { AdminRoleStore };
export default roles;
