import { SubuserPermission } from '@/state/server/subusers';
import { action, Action, thunk, Thunk } from 'easy-peasy';
import getSystemPermissions from '@/api/getSystemPermissions';

export interface GloablPermissionsStore {
    data: SubuserPermission[];
    setPermissions: Action<GloablPermissionsStore, SubuserPermission[]>;
    getPermissions: Thunk<GloablPermissionsStore, void, {}, any, Promise<void>>;
}

const permissions: GloablPermissionsStore = {
    data: [],

    setPermissions: action((state, payload) => {
        state.data = payload;
    }),

    getPermissions: thunk(async (actions) => {
        const permissions = await getSystemPermissions();

        actions.setPermissions(permissions);
    }),
};

export default permissions;
