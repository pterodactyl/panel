import { createContextStore } from 'easy-peasy';
import { composeWithDevTools } from 'redux-devtools-extension';

import nests, { AdminNestStore } from '@/state/admin/nests';
import roles, { AdminRoleStore } from '@/state/admin/roles';
import users, { AdminUserStore } from '@/state/admin/users';

interface AdminStore {
    nests: AdminNestStore;
    roles: AdminRoleStore;
    users: AdminUserStore;
}

export const AdminContext = createContextStore<AdminStore>({
    nests,
    roles,
    users,
}, {
    compose: composeWithDevTools({
        name: 'AdminStore',
        trace: true,
    }),
});
