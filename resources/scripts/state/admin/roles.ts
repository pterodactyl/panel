import { action, Action } from 'easy-peasy';
import { Role } from '@/api/admin/roles/getRoles';

export interface AdminRoleStore {
    data: Role[];
    selectedRoles: number[];

    setRoles: Action<AdminRoleStore, Role[]>;
    appendRole: Action<AdminRoleStore, Role>;
    removeRole: Action<AdminRoleStore, number>;

    setSelectedRoles: Action<AdminRoleStore, number[]>;
    appendSelectedRole: Action<AdminRoleStore, number>;
    removeSelectedRole: Action<AdminRoleStore, number>;
}

const roles: AdminRoleStore = {
    data: [],
    selectedRoles: [],

    setRoles: action((state, payload) => {
        state.data = payload;
    }),

    appendRole: action((state, payload) => {
        if (state.data.find(role => role.id === payload.id)) {
            state.data = state.data.map(role => role.id === payload.id ? payload : role);
        } else {
            state.data = [ ...state.data, payload ];
        }
    }),

    removeRole: action((state, payload) => {
        state.data = [ ...state.data.filter(role => role.id !== payload) ];
    }),

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

export default roles;
