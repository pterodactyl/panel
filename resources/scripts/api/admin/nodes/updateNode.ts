import http from '@/api/http';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';

export default (id: number, node: Partial<Node>, include: string[] = []): Promise<Node> => {
    const data = {};

    Object.keys(node).forEach(key => {
        const key2 = key
            .replace('HTTP', 'Http')
            .replace('SFTP', 'Sftp')
            .replace(/[A-Z]/g, letter => `_${letter.toLowerCase()}`);
        // @ts-expect-error todo
        data[key2] = node[key];
    });

    return new Promise((resolve, reject) => {
        http.patch(`/api/application/nodes/${id}`, data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToNode(data)))
            .catch(reject);
    });
};
