import http from '@/api/http';
import { Transformers, ServerDatabase } from '@definitions/user';

const createServerDatabase = (uuid: string, data: { connectionsFrom: string; databaseName: string }): Promise<ServerDatabase> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/databases`, {
            database: data.databaseName,
            remote: data.connectionsFrom,
        }, {
            params: { include: 'password' },
        })
            .then(response => resolve(Transformers.toServerDatabase(response.data.attributes)))
            .catch(reject);
    });
};

const getServerDatabases = (uuid: string, includePassword = true): Promise<ServerDatabase[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/databases`, {
            params: includePassword ? { include: 'password' } : undefined,
        })
            .then(response => resolve(
                (response.data.data || []).map((item: any) => Transformers.toServerDatabase(item.attributes))
            ))
            .catch(reject);
    });
};

const deleteServerDatabase = (uuid: string, database: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/databases/${database}`)
            .then(() => resolve())
            .catch(reject);
    });
};

const rotateDatabasePassword = (uuid: string, database: string): Promise<ServerDatabase> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/databases/${database}/rotate-password`)
            .then((response) => resolve(Transformers.toServerDatabase(response.data)))
            .catch(reject);
    });
};

export {
    createServerDatabase,
    getServerDatabases,
    deleteServerDatabase,
    rotateDatabasePassword,
};
