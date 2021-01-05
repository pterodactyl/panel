import { createContextStore } from 'easy-peasy';
import { composeWithDevTools } from 'redux-devtools-extension';

import nests, { AdminNestStore } from '@/state/admin/nests';
import roles, { AdminRoleStore } from '@/state/admin/roles';
import servers, { AdminServerStore } from '@/state/admin/servers';
import users, { AdminUserStore } from '@/state/admin/users';

interface AdminStore {
    nests: AdminNestStore;
    roles: AdminRoleStore;
    servers: AdminServerStore;
    users: AdminUserStore;
}

export const AdminContext = createContextStore<AdminStore>({
    nests,
    roles,
    servers,
    users,
}, {
    compose: composeWithDevTools({
        name: 'AdminStore',
        trace: true,
    }),
});
