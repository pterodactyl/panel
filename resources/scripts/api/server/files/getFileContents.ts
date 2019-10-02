import http from '@/api/http';

export default (server: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/files/contents`, {
            params: { file },
            transformResponse: res => res,
            responseType: 'text/plain',
        })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
