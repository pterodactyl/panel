import { createContextStore } from 'easy-peasy';
import { composeWithDevTools } from 'redux-devtools-extension';

import nests, { AdminNestStore } from '@/state/admin/nests';
import roles, { AdminRoleStore } from '@/state/admin/roles';

interface AdminStore {
    nests: AdminNestStore
    roles: AdminRoleStore;
}

export const AdminContext = createContextStore<AdminStore>({
    nests,
    roles,
}, {
    compose: composeWithDevTools({
        name: 'AdminStore',
        trace: true,
    }),
});
