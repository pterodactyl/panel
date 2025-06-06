import http from '@/api/http';

export default (uuid: string, schedule: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/schedules/${schedule}`)
            .then(() => resolve())
            .catch(reject);
    });
};
