import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export interface Values {
    externalId: string;
    name: string;
    ownerId: number;
    oomKiller: boolean;

    memory: number;
    swap: number;
    disk: number;
    io: number;
    cpu: number;
    threads: string;

    databases: number;
    allocations: number;
    backups: number;
}

export default (id: number, server: Partial<Values>, include: string[] = []): Promise<Server> => {
    const data = {};

    Object.keys(server).forEach((key) => {
        const key2 = key.replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
        // @ts-ignore
        data[key2] = server[key];
    });
    console.log(data);

    return new Promise((resolve, reject) => {
        http.patch(`/api/application/servers/${id}`, data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
