import { action, Action, thunk, Thunk } from 'easy-peasy';
import getServerSubusers from '@/api/server/users/getServerSubusers';

export type SubuserPermission = string;

export interface Subuser {
    uuid: string;
    username: string;
    email: string;
    image: string;
    twoFactorEnabled: boolean;
    createdAt: Date;
    permissions: SubuserPermission[];

    can (permission: SubuserPermission): boolean;
}

export interface ServerSubuserStore {
    data: Subuser[];
    setSubusers: Action<ServerSubuserStore, Subuser[]>;
    appendSubuser: Action<ServerSubuserStore, Subuser>;
    getSubusers: Thunk<ServerSubuserStore, string, any, {}, Promise<void>>;
}

const subusers: ServerSubuserStore = {
    data: [],

    setSubusers: action((state, payload) => {
        state.data = payload;
    }),

    appendSubuser: action((state, payload) => {
        state.data = [...state.data, payload];
    }),

    getSubusers: thunk(async (actions, payload) => {
        const subusers = await getServerSubusers(payload);

        actions.setSubusers(subusers);
    }),
};

export default subusers;
