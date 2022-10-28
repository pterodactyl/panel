import http from '@/api/http';

export interface ServerDatabase {
    id: string;
    name: string;
    username: string;
    connectionString: string;
    allowConnectionsFrom: string;
    password?: string;
}

export const rawDataToServerDatabase = (data: any): ServerDatabase => ({
    id: data.id,
    name: data.name,
    username: data.username,
    connectionString: `${data.host.address}:${data.host.port}`,
    allowConnectionsFrom: data.connections_from,
    password: data.relationships.password?.attributes?.password,
});

export default (uuid: string, includePassword = true): Promise<ServerDatabase[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/databases`, {
            params: includePassword ? { include: 'password' } : undefined,
        })
            .then(response =>
                resolve((response.data.data || []).map((item: any) => rawDataToServerDatabase(item.attributes))),
            )
            .catch(reject);
    });
};
