import http from '@/api/http';

export default (server: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/files/contents`, {
            params: { file: file.split('/').map(item => encodeURIComponent(item)).join('/') },
            transformResponse: res => res,
            responseType: 'text',
        })
            .then(({ data }) => resolve(data))
            .catch(reject);
    });
};
