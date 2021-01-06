import http from '@/api/http';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';

export default (id: number): Promise<Node> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}`)
            .then(({ data }) => resolve(rawDataToNode(data)))
            .catch(reject);
    });
};
