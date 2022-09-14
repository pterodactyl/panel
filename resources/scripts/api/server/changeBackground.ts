import http from '@/api/http';

export default (uuid: string, bg: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/background`, { bg })
            .then(() => resolve())
            .catch(reject);
    });
};
