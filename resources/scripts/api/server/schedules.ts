import http from '@/api/http';
import { Schedule, Task, Transformers } from '@definitions/user';

type CreateScheduleData = Pick<Schedule, 'cron' | 'name' | 'onlyWhenOnline' | 'isActive'> & { id?: number }

interface CreateTaskData {
    action: string;
    payload: string;
    timeOffset: string | number;
    continueOnFailure: boolean;
}

const createOrUpdateSchedule = async (uuid: string, schedule: CreateScheduleData): Promise<Schedule> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/schedules${schedule.id ? `/${schedule.id}` : ''}`, {
        is_active: schedule.isActive,
        only_when_online: schedule.onlyWhenOnline,
        name: schedule.name,
        minute: schedule.cron.minute,
        hour: schedule.cron.hour,
        day_of_month: schedule.cron.dayOfMonth,
        month: schedule.cron.month,
        day_of_week: schedule.cron.dayOfWeek,
    });

    return Transformers.toServerSchedule(data);
};

const createOrUpdateScheduleTask = async (uuid: string, schedule: number, task: number | undefined, data: CreateTaskData): Promise<Task> => {
    const { data: response } = await http.post(`/api/client/servers/${uuid}/schedules/${schedule}/tasks${task ? `/${task}` : ''}`, {
        action: data.action,
        payload: data.payload,
        continue_on_failure: data.continueOnFailure,
        time_offset: data.timeOffset,
    });

    return Transformers.toServerTask(response);
};

const deleteSchedule = (uuid: string, schedule: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/schedules/${schedule}`)
            .then(() => resolve())
            .catch(reject);
    });
};

const deleteScheduleTask = (uuid: string, scheduleId: number, taskId: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/schedules/${scheduleId}/tasks/${taskId}`)
            .then(() => resolve())
            .catch(reject);
    });
};

const getServerSchedule = (uuid: string, schedule: number): Promise<Schedule> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/schedules/${schedule}`, {
            params: {
                include: [ 'tasks' ],
            },
        })
            .then(({ data }) => resolve(Transformers.toServerSchedule(data)))
            .catch(reject);
    });
};

const getServerSchedules = async (uuid: string): Promise<Schedule[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/schedules`, {
        params: {
            include: [ 'tasks' ],
        },
    });

    return (data.data || []).map(Transformers.toServerSchedule);
};

const triggerScheduleExecution = async (server: string, schedule: number): Promise<void> =>
    await http.post(`/api/client/servers/${server}/schedules/${schedule}/execute`);

export {
    deleteSchedule,
    deleteScheduleTask,
    createOrUpdateSchedule,
    createOrUpdateScheduleTask,
    getServerSchedule,
    getServerSchedules,
    triggerScheduleExecution,
};
