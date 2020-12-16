import http from '@/api/http';

interface Response {
    token: string;
    socket: string;
}

export default (server: string, transfer: boolean): Promise<Response> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${server}/websocket`, {
            params: {
                transfer,
            },
        })
            .then(({ data }) => resolve({
                token: data.data.token,
                socket: data.data.socket,
            }))
            .catch(reject);
    });
};
