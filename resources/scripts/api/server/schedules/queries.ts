import { useQuery, useMutation, useQueryClient, type QueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';

import { removeListItems, upsertListItem } from '@/api/queryData';
import {
    clientCreateScheduleTaskMutation,
    clientCreateServerScheduleMutation,
    clientDeleteScheduleTaskMutation,
    clientDeleteServerScheduleMutation,
    clientExecuteServerScheduleMutation,
    clientGetServerScheduleOptions,
    clientGetServerScheduleQueryKey,
    clientListServerSchedulesOptions,
    clientListServerSchedulesQueryKey,
    clientUpdateScheduleTaskMutation,
    clientUpdateServerScheduleMutation,
} from '@/api/generated/@tanstack/react-query.gen';
import type {
    ClientCreateScheduleTaskData,
    ClientCreateServerScheduleData,
    ClientDeleteScheduleTaskData,
    ClientDeleteServerScheduleData,
    ClientExecuteServerScheduleData,
    ClientGetServerScheduleData,
    ClientListServerSchedulesData,
    ClientListServerSchedulesResponse,
    ClientScheduleTaskResource,
    ClientServerScheduleResource,
    ClientUpdateScheduleTaskData,
    ClientUpdateServerScheduleData,
    Options,
} from '@/api/generated';
import { notifyHttpError } from '@/plugins/notifications';

export type Schedule = ClientServerScheduleResource;
export type Task = ClientScheduleTaskResource;

export type ScheduleValues = {
    id?: number;
    name: string;
    cron: {
        dayOfWeek: string;
        month: string;
        dayOfMonth: string;
        hour: string;
        minute: string;
    };
    isActive: boolean;
    onlyWhenOnline: boolean;
};

export type TaskValues = {
    action: ClientCreateScheduleTaskData['body']['action'];
    payload: string;
    timeOffset: string | number;
    continueOnFailure: boolean;
};

const serverSchedulesInput = (uuid: string): Options<ClientListServerSchedulesData> => ({
    path: { server_uuid: uuid },
    query: { include: 'tasks' },
});

const serverScheduleInput = (uuid: string, scheduleId: number): Options<ClientGetServerScheduleData> => ({
    path: { server_uuid: uuid, schedule_id: scheduleId },
    query: { include: 'tasks' },
});

const scheduleBody = (schedule: ScheduleValues): ClientCreateServerScheduleData['body'] => ({
    is_active: schedule.isActive,
    only_when_online: schedule.onlyWhenOnline,
    name: schedule.name,
    minute: schedule.cron.minute,
    hour: schedule.cron.hour,
    day_of_month: schedule.cron.dayOfMonth,
    month: schedule.cron.month,
    day_of_week: schedule.cron.dayOfWeek,
});

const taskBody = (task: TaskValues): ClientCreateScheduleTaskData['body'] => ({
    action: task.action,
    payload: task.payload,
    continue_on_failure: task.continueOnFailure,
    time_offset: Number(task.timeOffset),
});

export const createServerScheduleInput = (
    uuid: string,
    values: ScheduleValues
): Options<ClientCreateServerScheduleData> => ({
    path: { server_uuid: uuid },
    body: scheduleBody(values),
});

export const updateServerScheduleInput = (
    uuid: string,
    scheduleId: number,
    values: ScheduleValues
): Options<ClientUpdateServerScheduleData> => ({
    path: { server_uuid: uuid, schedule_id: scheduleId },
    body: scheduleBody(values),
});

export const deleteServerScheduleInput = (
    uuid: string,
    scheduleId: number
): Options<ClientDeleteServerScheduleData> => ({
    path: { server_uuid: uuid, schedule_id: scheduleId },
});

export const executeServerScheduleInput = (
    uuid: string,
    schedule: Schedule
): Options<ClientExecuteServerScheduleData> => ({
    path: { server_uuid: uuid, schedule_id: schedule.attributes.id },
});

export const createScheduleTaskInput = (
    uuid: string,
    schedule: Schedule,
    values: TaskValues
): Options<ClientCreateScheduleTaskData> => ({
    path: { server_uuid: uuid, schedule_id: schedule.attributes.id },
    body: taskBody(values),
});

export const updateScheduleTaskInput = (
    uuid: string,
    schedule: Schedule,
    taskId: number,
    values: TaskValues
): Options<ClientUpdateScheduleTaskData> => ({
    path: { server_uuid: uuid, schedule_id: schedule.attributes.id, task_id: taskId },
    body: taskBody(values),
});

export const deleteScheduleTaskInput = (
    uuid: string,
    schedule: Schedule,
    taskId: number
): Options<ClientDeleteScheduleTaskData> => ({
    path: { server_uuid: uuid, schedule_id: schedule.attributes.id, task_id: taskId },
});

const cacheServerSchedule = async (
    queryClient: QueryClient,
    uuid: string,
    schedule: Schedule,
    updater?: (current: Schedule) => Schedule
) => {
    const detailKey = clientGetServerScheduleQueryKey(serverScheduleInput(uuid, schedule.attributes.id));
    const listKey = clientListServerSchedulesQueryKey(serverSchedulesInput(uuid));

    await Promise.all([
        queryClient.cancelQueries({ queryKey: detailKey }),
        queryClient.cancelQueries({ queryKey: listKey }),
    ]);
    const updated = updater ? updater(queryClient.getQueryData<Schedule>(detailKey) ?? schedule) : schedule;

    queryClient.setQueryData(detailKey, updated);
    queryClient.setQueryData<ClientListServerSchedulesResponse>(listKey, (current) =>
        upsertListItem(current, updated, (item) => item.attributes.id === updated.attributes.id)
    );
};

const removeServerSchedule = async (queryClient: QueryClient, uuid: string, scheduleId: number) => {
    await queryClient.cancelQueries({ queryKey: clientListServerSchedulesQueryKey(serverSchedulesInput(uuid)) });

    return queryClient.setQueryData<ClientListServerSchedulesResponse>(
        clientListServerSchedulesQueryKey(serverSchedulesInput(uuid)),
        (current) => removeListItems(current, (schedule) => schedule.attributes.id === scheduleId)
    );
};

const cacheServerScheduleTask = (schedule: Schedule, task: Task): Schedule => {
    const tasksRelationship = schedule.attributes.relationships?.tasks;
    const tasks = tasksRelationship && 'data' in tasksRelationship ? tasksRelationship : { object: 'list', data: [] };
    const data = tasks.data.some((item) => item.attributes.id === task.attributes.id)
        ? tasks.data.map((item) => (item.attributes.id === task.attributes.id ? task : item))
        : [...tasks.data, task];

    return {
        ...schedule,
        attributes: {
            ...schedule.attributes,
            relationships: {
                ...schedule.attributes.relationships,
                tasks: { ...tasks, data },
            },
        },
    };
};

const removeServerScheduleTask = (schedule: Schedule, taskId: number): Schedule => {
    const tasks = schedule.attributes.relationships?.tasks;

    if (!tasks || !('data' in tasks)) {
        return schedule;
    }

    return {
        ...schedule,
        attributes: {
            ...schedule.attributes,
            relationships: {
                ...schedule.attributes.relationships,
                tasks: { ...tasks, data: tasks.data.filter((task) => task.attributes.id !== taskId) },
            },
        },
    };
};

export const serverSchedulesQueryOptions = (uuid: string) =>
    clientListServerSchedulesOptions(serverSchedulesInput(uuid));

export const useServerSchedules = (uuid: string) =>
    useQuery({ ...serverSchedulesQueryOptions(uuid), enabled: uuid.length > 0 });

export const serverScheduleQueryOptions = (uuid: string, scheduleId: number) =>
    clientGetServerScheduleOptions(serverScheduleInput(uuid, scheduleId));

export const useServerSchedule = (uuid: string, scheduleId: number) =>
    useQuery({
        ...serverScheduleQueryOptions(uuid, scheduleId),
        enabled: uuid.length > 0 && Number.isInteger(scheduleId) && scheduleId > 0,
    });

export const useCreateServerSchedule = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateServerScheduleMutation(),
        onSuccess: async (schedule, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, schedule);
            toast.success('Schedule created');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save schedule'),
    });
};

export const useUpdateServerSchedule = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateServerScheduleMutation(),
        onSuccess: async (schedule, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, schedule);
            toast.success('Schedule saved');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save schedule'),
    });
};

export const useDeleteServerSchedule = () => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteServerScheduleMutation(),
        onSuccess: async (_data, { path }) => {
            queryClient.removeQueries({
                queryKey: clientGetServerScheduleQueryKey(serverScheduleInput(path.server_uuid, path.schedule_id)),
            });
            await removeServerSchedule(queryClient, path.server_uuid, path.schedule_id);
            toast.success('Schedule deleted');
        },
        onError: (error) => notifyHttpError(error, 'Unable to delete schedule'),
    });
};

export const useTriggerServerScheduleExecution = (schedule: Schedule) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientExecuteServerScheduleMutation(),
        onSuccess: async (_data, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, {
                ...schedule,
                attributes: { ...schedule.attributes, is_processing: true },
            });
            toast.success('Schedule queued');
        },
        onError: (error) => notifyHttpError(error, 'Unable to run schedule'),
    });
};

export const useCreateServerScheduleTask = (schedule: Schedule) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientCreateScheduleTaskMutation(),
        onSuccess: async (task, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, schedule, (current) =>
                cacheServerScheduleTask(current, task)
            );
            toast.success('Task created');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save task'),
    });
};

export const useUpdateServerScheduleTask = (schedule: Schedule) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientUpdateScheduleTaskMutation(),
        onSuccess: async (task, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, schedule, (current) =>
                cacheServerScheduleTask(current, task)
            );
            toast.success('Task saved');
        },
        onError: (error) => notifyHttpError(error, 'Unable to save task'),
    });
};

export const useDeleteServerScheduleTask = (schedule: Schedule) => {
    const queryClient = useQueryClient();

    return useMutation({
        ...clientDeleteScheduleTaskMutation(),
        onSuccess: async (_data, { path }) => {
            await cacheServerSchedule(queryClient, path.server_uuid, schedule, (current) =>
                removeServerScheduleTask(current, path.task_id)
            );
            toast.success('Task deleted');
        },
        onError: (error) => notifyHttpError(error, 'Unable to delete task'),
    });
};
