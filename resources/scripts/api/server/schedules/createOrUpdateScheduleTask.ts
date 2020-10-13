import { rawDataToServerTask, Task } from '@/api/server/schedules/getServerSchedules';
import http from '@/api/http';

interface Data {
    action: string;
    payload: string;
    timeOffset: string | number;
}

export default (uuid: string, schedule: number, task: number | undefined, { timeOffset, ...data }: Data): Promise<Task> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/schedules/${schedule}/tasks${task ? `/${task}` : ''}`, {
            ...data,
            time_offset: timeOffset,
        })
            .then(({ data }) => resolve(rawDataToServerTask(data.attributes)))
            .catch(reject);
    });
};
