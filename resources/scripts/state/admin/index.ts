import { createContextStore } from 'easy-peasy';

import type { AdminAllocationStore } from '@/state/admin/allocations';
import allocations from '@/state/admin/allocations';
import type { AdminDatabaseStore } from '@/state/admin/databases';
import databases from '@/state/admin/databases';
import type { AdminLocationStore } from '@/state/admin/locations';
import locations from '@/state/admin/locations';
import type { AdminMountStore } from '@/state/admin/mounts';
import mounts from '@/state/admin/mounts';
import type { AdminNestStore } from '@/state/admin/nests';
import nests from '@/state/admin/nests';
import type { AdminNodeStore } from '@/state/admin/nodes';
import nodes from '@/state/admin/nodes';
import type { AdminRoleStore } from '@/state/admin/roles';
import roles from '@/state/admin/roles';
import type { AdminServerStore } from '@/state/admin/servers';
import servers from '@/state/admin/servers';
import type { AdminUserStore } from '@/state/admin/users';
import users from '@/state/admin/users';

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
});
