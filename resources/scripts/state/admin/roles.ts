import { action, Action } from 'easy-peasy';
import { Role } from '@/api/admin/roles/getRoles';

export interface AdminRoleStore {
    data: Role[];
    setRoles: Action<AdminRoleStore, Role[]>;
    appendRole: Action<AdminRoleStore, Role>;
    removeRole: Action<AdminRoleStore, number>;
}

const roles: AdminRoleStore = {
    data: [],

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
};

export default roles;
