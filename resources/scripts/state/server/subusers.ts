import { action, Action, thunk, Thunk } from 'easy-peasy';
import getServerSubusers from '@/api/server/users/getServerSubusers';

export type SubuserPermission =
    'websocket.*' |
    'control.console' | 'control.start' | 'control.stop' | 'control.restart' | 'control.kill' |
    'user.create' | 'user.read' | 'user.update' | 'user.delete' |
    'file.create' | 'file.read' | 'file.update' | 'file.delete' | 'file.archive' | 'file.sftp' |
    'allocation.read' | 'allocation.update' |
    'startup.read' | 'startup.update' |
    'database.create' | 'database.read' | 'database.update' | 'database.delete' | 'database.view_password' |
    'schedule.create' | 'schedule.read' | 'schedule.update' | 'schedule.delete'
    ;

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
        state.data = [ ...state.data, payload ];
    }),

    getSubusers: thunk(async (actions, payload) => {
        const subusers = await getServerSubusers(payload);

        actions.setSubusers(subusers);
    }),
};

export default subusers;
