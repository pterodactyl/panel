import { createContextStore } from 'easy-peasy';
import { composeWithDevTools } from 'redux-devtools-extension';

import allocations, { AdminAllocationStore } from '@/state/admin/allocations';
import databases, { AdminDatabaseStore } from '@/state/admin/databases';
import locations, { AdminLocationStore } from '@/state/admin/locations';
import mounts, { AdminMountStore } from '@/state/admin/mounts';
import nests, { AdminNestStore } from '@/state/admin/nests';
import nodes, { AdminNodeStore } from '@/state/admin/nodes';
import roles, { AdminRoleStore } from '@/state/admin/roles';
import servers, { AdminServerStore } from '@/state/admin/servers';
import users, { AdminUserStore } from '@/state/admin/users';

interface AdminStore {
    allocations: AdminAllocationStore;
    databases: AdminDatabaseStore;
    locations: AdminLocationStore;
    mounts: AdminMountStore;
    nests: AdminNestStore;
    nodes: AdminNodeStore;
    roles: AdminRoleStore;
    servers: AdminServerStore;
    users: AdminUserStore;
}

export const AdminContext = createContextStore<AdminStore>({
    allocations,
    databases,
    locations,
    mounts,
    nests,
    nodes,
    roles,
    servers,
    users,
}, {
    compose: composeWithDevTools({
        name: 'AdminStore',
        trace: true,
    }),
});
