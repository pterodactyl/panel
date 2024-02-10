import http from '@/api/http';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';

export interface Values {
    name: string;
    locationId: number;
    databaseHostId: number | null;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    public: boolean;
    daemonBase: string;

    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;

    listenPortHTTP: number;
    publicPortHTTP: number;
    listenPortSFTP: number;
    publicPortSFTP: number;
}

export default (values: Values, include: string[] = []): Promise<Node> => {
    const data = {};

    Object.keys(values).forEach(key => {
        const key2 = key
            .replace('HTTP', 'Http')
            .replace('SFTP', 'Sftp')
            .replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
        // @ts-expect-error todo
        data[key2] = values[key];
    });

    return new Promise((resolve, reject) => {
        http.post('/api/application/nodes', data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToNode(data)))
            .catch(reject);
    });
};
