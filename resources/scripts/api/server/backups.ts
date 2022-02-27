import http from '@/api/http';
import { ServerBackup, Transformers } from '@definitions/user';

const restoreServerBackup = async (uuid: string, backup: string, truncate?: boolean): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/backups/${backup}/restore`, {
        truncate,
    });
};

interface CreateBackupParams {
    name?: string;
    ignored?: string;
    isLocked: boolean;
}

const createServerBackup = async (uuid: string, params: CreateBackupParams): Promise<ServerBackup> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/backups`, {
        name: params.name,
        ignored: params.ignored,
        is_locked: params.isLocked,
    });

    return Transformers.toServerBackup(data);
};

const deleteBackup = (uuid: string, backup: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/backups/${backup}`)
            .then(() => resolve())
            .catch(reject);
    });
};

const getBackupDownloadUrl = (uuid: string, backup: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/backups/${backup}/download`)
            .then(({ data }) => resolve(data.attributes.url))
            .catch(reject);
    });
};

export {
    deleteBackup,
    restoreServerBackup,
    createServerBackup,
    getBackupDownloadUrl,
};
