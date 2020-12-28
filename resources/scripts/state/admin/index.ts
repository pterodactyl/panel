import { createContextStore } from 'easy-peasy';
import { composeWithDevTools } from 'redux-devtools-extension';
import roles, { AdminRoleStore } from '@/state/admin/roles';

interface AdminStore {
    roles: AdminRoleStore;
}

export const AdminContext = createContextStore<AdminStore>({
    roles,
}, {
    compose: composeWithDevTools({
        name: 'AdminStore',
        trace: true,
    }),
});
