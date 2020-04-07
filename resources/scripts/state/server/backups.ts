import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { action, Action } from 'easy-peasy';

export interface ServerBackupStore {
    data: ServerBackup[];
    setBackups: Action<ServerBackupStore, ServerBackup[]>;
    appendBackup: Action<ServerBackupStore, ServerBackup>;
    removeBackup: Action<ServerBackupStore, string>;
}

const backups: ServerBackupStore = {
    data: [],

    setBackups: action((state, payload) => {
        state.data = payload;
    }),

    appendBackup: action((state, payload) => {
        if (state.data.find(backup => backup.uuid === payload.uuid)) {
            state.data = state.data.map(backup => backup.uuid === payload.uuid ? payload : backup);
        } else {
            state.data = [ ...state.data, payload ];
        }
    }),

    removeBackup: action((state, payload) => {
        state.data = [ ...state.data.filter(backup => backup.uuid !== payload) ];
    }),
};

export default backups;
