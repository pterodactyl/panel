import http from '@/api/http';

export interface ShareResponse {
    key: string;
    domain: string;
}

export default (uuid: string, data: string): Promise<ShareResponse> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/console`, { data })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
