import http from '@/api/http';

export interface Schedule {
    id: number;
    name: string;
    cron: {
        dayOfWeek: string;
        dayOfMonth: string;
        hour: string;
        minute: string;
    };
    isActive: boolean;
    isProcessing: boolean;
    lastRunAt: Date | null;
    nextRunAt: Date | null;
    createdAt: Date;
    updatedAt: Date;

    tasks: Task[];
}

export interface Task {
    id: number;
    sequenceId: number;
    action: string;
    payload: string;
    timeOffset: number;
    isQueued: boolean;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToServerTask = (data: any): Task => ({
    id: data.id,
    sequenceId: data.sequence_id,
    action: data.action,
    payload: data.payload,
    timeOffset: data.time_offset,
    isQueued: data.is_queued,
    createdAt: new Date(data.created_at),
    updatedAt: new Date(data.updated_at),
});

export const rawDataToServerSchedule = (data: any): Schedule => ({
    id: data.id,
    name: data.name,
    cron: {
        dayOfWeek: data.cron.day_of_week,
        dayOfMonth: data.cron.day_of_month,
        hour: data.cron.hour,
        minute: data.cron.minute,
    },
    isActive: data.is_active,
    isProcessing: data.is_processing,
    lastRunAt: data.last_run_at ? new Date(data.last_run_at) : null,
    nextRunAt: data.next_run_at ? new Date(data.next_run_at) : null,
    createdAt: new Date(data.created_at),
    updatedAt: new Date(data.updated_at),

    tasks: (data.relationships?.tasks?.data || []).map((row: any) => rawDataToServerTask(row.attributes)),
});

export default (uuid: string): Promise<Schedule[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/schedules`, {
            params: {
                include: [ 'tasks' ],
            },
        })
            .then(({ data }) => resolve((data.data || []).map((row: any) => rawDataToServerSchedule(row.attributes))))
            .catch(reject);
    });
};
