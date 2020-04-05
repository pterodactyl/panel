import http from '@/api/http';

export default (uuid: string, userId: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/users/${userId}`)
            .then(() => resolve())
            .catch(reject);
    });
};
