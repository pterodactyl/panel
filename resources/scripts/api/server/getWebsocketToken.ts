import http from '@/api/http';

export default (server: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/websocket`)
            .then(response => resolve(response.data.data.socket))
            .catch(reject);
    });
};
