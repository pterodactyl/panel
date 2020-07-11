import http from '@/api/http';

export default (uuid: string, scheduleId: number, taskId: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/schedules/${scheduleId}/tasks/${taskId}`)
            .then(() => resolve())
            .catch(reject);
    });
};
