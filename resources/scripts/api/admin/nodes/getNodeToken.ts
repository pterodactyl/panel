import http from '@/api/http';

export interface NodeTokenResponse {
    debug: boolean;
    node: number;
    token: string;
    remote: string;
}

export default (id: number): Promise<NodeTokenResponse> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/nodes/${id}/token`)
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
