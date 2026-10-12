import { useCallback, useMemo } from 'react';
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { useNavigate, useParams } from '@tanstack/react-router';
import { httpErrorToHuman } from '@/api/http';
import { relationshipData } from '@/api/relationships';
import {
    createScheduleTaskInput,
    createServerScheduleInput,
    deleteScheduleTaskInput,
    deleteServerScheduleInput,
    executeServerScheduleInput,
    serverScheduleQueryOptions,
    updateScheduleTaskInput,
    updateServerScheduleInput,
    useCreateServerSchedule,
    useCreateServerScheduleTask,
    useDeleteServerSchedule,
    useDeleteServerScheduleTask,
    useServerSchedule,
    useServerSchedules,
    useTriggerServerScheduleExecution,
    useUpdateServerSchedule,
    useUpdateServerScheduleTask,
    type Schedule,
    type ScheduleValues,
    type Task,
} from '@/api/server/schedules/queries';
import { usePermissions } from '@/plugins/usePermissions';
import { implementViews } from '../registry';
import { attempt } from './attempt';
import { useLoadedServer } from './loadedServer';

export interface ScheduleCron {
    readonly minute: string;
    readonly hour: string;
    readonly dayOfMonth: string;
    readonly month: string;
    readonly dayOfWeek: string;
}
export interface ScheduleSummary {
    readonly id: number;
    readonly name: string;
    readonly cron: ScheduleCron;
    readonly active: boolean;
    /** A run is in progress. */
    readonly processing: boolean;
    readonly onlyWhenOnline: boolean;
    readonly lastRunAt: string | null;
    readonly nextRunAt: string | null;
}
/** What the schedule form edits; the cron fields take cron syntax. */
export interface ScheduleFormValues extends ScheduleCron {
    readonly name: string;
    readonly enabled: boolean;
    readonly onlyWhenOnline: boolean;
}

export type ScheduleTaskAction = 'command' | 'power' | 'backup';
export interface ScheduleTask {
    readonly id: number;
    /** The task's place in the run, from 1. */
    readonly sequence: number;
    readonly action: ScheduleTaskAction;
    /** The command, the power signal, or the files a backup ignores. */
    readonly payload: string;
    /** Seconds to wait after the previous task; the first task runs at once. */
    readonly timeOffset: number;
    readonly continueOnFailure: boolean;
}
export interface ScheduleTaskValues {
    readonly action: ScheduleTaskAction;
    readonly payload: string;
    readonly timeOffset: number | null;
    readonly continueOnFailure: boolean;
}
export interface ScheduleTaskOption<T extends string> {
    readonly value: T;
    readonly label: string;
}
/** The options, defaults and validation of the task form. */
export interface ScheduleTaskForm {
    readonly actions: readonly ScheduleTaskOption<ScheduleTaskAction>[];
    readonly powerSignals: readonly ScheduleTaskOption<string>[];
    /** The values a task's form starts from, or a new task's. */
    readonly values: (task?: ScheduleTask) => ScheduleTaskValues;
    /** The payload to show once the form's action changes. */
    readonly payloadFor: (action: ScheduleTaskAction, task?: ScheduleTask) => string;
    readonly validateTimeOffset: (value: number | null) => string | undefined;
    readonly validatePayload: (action: ScheduleTaskAction, payload: string) => string | undefined;
    /** Why the values cannot be saved at all, checked when the form is submitted. */
    readonly validate: (values: ScheduleTaskValues) => string | null;
}

export interface SchedulesPageModel {
    /** The schedules failed to load, so the page shows this message instead. */
    readonly error: string | null;
    readonly retry: () => void;
}
export interface ScheduleListModel {
    /** The server's identifier, which links to each schedule include. */
    readonly serverId: string;
    readonly schedules: readonly ScheduleSummary[];
    readonly loading: boolean;
    readonly canCreate: boolean;
    readonly canUpdate: boolean;
    /** The values a new schedule's form starts from. */
    readonly defaults: ScheduleFormValues;
    /** Resolves whether the schedule was created; a failure has already been reported. */
    readonly create: (values: ScheduleFormValues) => Promise<boolean>;
}

export interface ScheduleDetailPageModel {
    readonly loading: boolean;
    /** The schedule failed to load, so the page shows this message instead. */
    readonly error: string | null;
    readonly retry: () => void;
}
export interface ScheduleModel {
    readonly schedule: ScheduleSummary;
    readonly taskCount: number;
    readonly canUpdate: boolean;
    readonly canDelete: boolean;
    /** The values the edit form starts from. */
    readonly values: ScheduleFormValues;
    readonly running: boolean;
    readonly deleting: boolean;
    readonly taskForm: ScheduleTaskForm;
    /** Each resolves whether it succeeded; a failure has already been reported. */
    readonly actions: {
        readonly update: (values: ScheduleFormValues) => Promise<boolean>;
        readonly run: () => Promise<boolean>;
        /** Returns to the schedule list once the schedule is deleted. */
        readonly remove: () => Promise<boolean>;
        readonly createTask: (values: ScheduleTaskValues) => Promise<boolean>;
    };
}
export interface ScheduleTasksModel {
    /** In the order they run. */
    readonly tasks: readonly ScheduleTask[];
    readonly canUpdate: boolean;
    readonly deleting: boolean;
    readonly form: ScheduleTaskForm;
    /** Each resolves whether it succeeded; a failure has already been reported. */
    readonly actions: {
        readonly create: (values: ScheduleTaskValues) => Promise<boolean>;
        readonly update: (id: number, values: ScheduleTaskValues) => Promise<boolean>;
        readonly remove: (id: number) => Promise<boolean>;
    };
}

declare module '../registry' {
    interface ViewModels {
        'page.server.schedules': SchedulesPageModel;
        'page.server.schedules.detail': ScheduleDetailPageModel;
        'server.schedules': ScheduleListModel;
        'server.schedule': ScheduleModel;
        'server.schedule.tasks': ScheduleTasksModel;
    }
}

const newSchedule: ScheduleFormValues = {
    name: '',
    minute: '*/5',
    hour: '*',
    dayOfMonth: '*',
    month: '*',
    dayOfWeek: '*',
    enabled: true,
    onlyWhenOnline: true,
};

const scheduleSummary = ({ attributes }: Schedule): ScheduleSummary => ({
    id: attributes.id,
    name: attributes.name,
    cron: {
        minute: attributes.cron.minute,
        hour: attributes.cron.hour,
        dayOfMonth: attributes.cron.day_of_month,
        month: attributes.cron.month,
        dayOfWeek: attributes.cron.day_of_week,
    },
    active: attributes.is_active,
    processing: attributes.is_processing,
    onlyWhenOnline: attributes.only_when_online,
    lastRunAt: attributes.last_run_at,
    nextRunAt: attributes.next_run_at,
});

const scheduleFormValues = ({ name, cron, active, onlyWhenOnline }: ScheduleSummary): ScheduleFormValues => ({
    name: name || newSchedule.name,
    minute: cron.minute || newSchedule.minute,
    hour: cron.hour || newSchedule.hour,
    dayOfMonth: cron.dayOfMonth || newSchedule.dayOfMonth,
    month: cron.month || newSchedule.month,
    dayOfWeek: cron.dayOfWeek || newSchedule.dayOfWeek,
    enabled: active,
    onlyWhenOnline,
});

const scheduleValues = (values: ScheduleFormValues): ScheduleValues => ({
    name: values.name,
    cron: {
        minute: values.minute,
        hour: values.hour,
        dayOfWeek: values.dayOfWeek,
        month: values.month,
        dayOfMonth: values.dayOfMonth,
    },
    onlyWhenOnline: values.onlyWhenOnline,
    isActive: values.enabled,
});

const scheduleTask = ({ attributes }: Task): ScheduleTask => ({
    id: attributes.id,
    sequence: attributes.sequence_id,
    action: attributes.action,
    payload: attributes.payload,
    timeOffset: attributes.time_offset,
    continueOnFailure: attributes.continue_on_failure,
});

const orderedTasks = (schedule: Schedule): ScheduleTask[] =>
    relationshipData(schedule.attributes.relationships?.tasks)
        .map(scheduleTask)
        .sort((a, b) => a.sequence - b.sequence);

const validateTimeOffset = (value: number | null): string | undefined => {
    if (value === null) {
        return 'A time offset must be provided.';
    }

    if (value < 0) {
        return 'The time offset must be at least 0 seconds.';
    }

    if (value > 900) {
        return 'The time offset must be less than 900 seconds.';
    }

    return undefined;
};

/** The task form for a server allowed `backupLimit` backups. */
export function scheduleTaskForm(backupLimit: number | null): ScheduleTaskForm {
    const initialAction = (task?: ScheduleTask): ScheduleTaskAction => task?.action || 'command';

    return {
        actions: [
            { value: 'command', label: 'Send command' },
            { value: 'power', label: 'Send power action' },
            { value: 'backup', label: 'Create backup' },
        ],
        powerSignals: [
            { value: 'start', label: 'Start the server' },
            { value: 'restart', label: 'Restart the server' },
            { value: 'stop', label: 'Stop the server' },
            { value: 'kill', label: 'Terminate the server' },
        ],
        values: (task) => ({
            action: initialAction(task),
            payload: task?.payload || '',
            timeOffset: task?.timeOffset ?? 0,
            continueOnFailure: task?.continueOnFailure || false,
        }),
        payloadFor: (action, task) => {
            if (action === initialAction(task)) {
                return task?.payload || '';
            }

            return action === 'power' ? 'start' : '';
        },
        validateTimeOffset,
        validatePayload: (action, payload) =>
            action !== 'command' || payload.length > 0 ? undefined : 'A task payload must be provided.',
        validate: ({ action }) =>
            backupLimit === 0 && action === 'backup'
                ? "A backup task cannot be created when the server's backup limit is set to 0."
                : null,
    };
}

function useScheduleId(): number {
    const { scheduleId } = useParams({ strict: false });

    return Number(scheduleId);
}

/**
 * The route's schedule, which the detail page loads before its blocks render. A deleted schedule's
 * blocks keep its last copy while the page navigates away, rather than fetching it again.
 */
function useLoadedSchedule(): Schedule {
    const { uuid } = useLoadedServer().attributes;
    const { data } = useQuery({
        ...serverScheduleQueryOptions(uuid, useScheduleId()),
        enabled: false,
        placeholderData: keepPreviousData,
    });

    if (!data) {
        throw new Error('Schedule views render once the detail page has loaded the schedule.');
    }

    return data;
}

function useTaskForm(): ScheduleTaskForm {
    const backupLimit = useLoadedServer().attributes.feature_limits.backups;

    return useMemo(() => scheduleTaskForm(backupLimit), [backupLimit]);
}

/** Creates a task on the schedule; the time offset must have passed validation. */
function useCreateTask(schedule: Schedule): (values: ScheduleTaskValues) => Promise<boolean> {
    const { uuid } = useLoadedServer().attributes;
    const { mutateAsync } = useCreateServerScheduleTask(schedule);

    return useCallback(
        ({ timeOffset, ...values }: ScheduleTaskValues) =>
            attempt(() =>
                mutateAsync(createScheduleTaskInput(uuid, schedule, { ...values, timeOffset: timeOffset ?? 0 }))
            ),
        [mutateAsync, uuid, schedule]
    );
}

function useSchedulesPageModel(): SchedulesPageModel {
    const { uuid } = useLoadedServer().attributes;
    const { data, error, refetch } = useServerSchedules(uuid);
    const failed = error && !data ? httpErrorToHuman(error) : null;

    return useMemo(() => ({ error: failed, retry: () => void refetch() }), [failed, refetch]);
}

function useScheduleListModel(): ScheduleListModel {
    const { uuid, identifier } = useLoadedServer().attributes;
    const { data, isLoading } = useServerSchedules(uuid);
    const [canCreate, canUpdate] = usePermissions(['schedule.create', 'schedule.update']);
    const { mutateAsync } = useCreateServerSchedule();
    const create = useCallback(
        (values: ScheduleFormValues) =>
            attempt(() => mutateAsync(createServerScheduleInput(uuid, scheduleValues(values)))),
        [mutateAsync, uuid]
    );

    return useMemo(
        () => ({
            serverId: identifier,
            schedules: (data?.data ?? []).map(scheduleSummary),
            loading: isLoading,
            canCreate,
            canUpdate,
            defaults: newSchedule,
            create,
        }),
        [identifier, data, isLoading, canCreate, canUpdate, create]
    );
}

function useScheduleDetailPageModel(): ScheduleDetailPageModel {
    const { uuid } = useLoadedServer().attributes;
    const { data, error, isFetching, refetch } = useServerSchedule(uuid, useScheduleId());
    const failed = !data && error && !isFetching ? httpErrorToHuman(error) : null;

    return useMemo(
        () => ({ loading: !data && failed === null, error: failed, retry: () => void refetch() }),
        [data, failed, refetch]
    );
}

function useScheduleModel(): ScheduleModel {
    const navigate = useNavigate();
    const { uuid, identifier } = useLoadedServer().attributes;
    const schedule = useLoadedSchedule();
    const [canUpdate, canDelete] = usePermissions(['schedule.update', 'schedule.delete']);
    const { mutateAsync: updateSchedule } = useUpdateServerSchedule();
    const { mutateAsync: runSchedule, isPending: running } = useTriggerServerScheduleExecution(schedule);
    const { mutateAsync: deleteSchedule, isPending: deleting } = useDeleteServerSchedule();
    const createTask = useCreateTask(schedule);
    const taskForm = useTaskForm();
    const { id } = schedule.attributes;

    const actions = useMemo(
        () => ({
            update: (values: ScheduleFormValues) =>
                attempt(() => updateSchedule(updateServerScheduleInput(uuid, id, scheduleValues(values)))),
            run: () => attempt(() => runSchedule(executeServerScheduleInput(uuid, schedule))),
            remove: async () => {
                const removed = await attempt(() => deleteSchedule(deleteServerScheduleInput(uuid, id)));

                if (removed) {
                    void navigate({ to: '/server/$id/schedules', params: { id: identifier } });
                }

                return removed;
            },
            createTask,
        }),
        [updateSchedule, runSchedule, deleteSchedule, createTask, navigate, uuid, identifier, id, schedule]
    );

    return useMemo(() => {
        const summary = scheduleSummary(schedule);

        return {
            schedule: summary,
            taskCount: relationshipData(schedule.attributes.relationships?.tasks).length,
            canUpdate,
            canDelete,
            values: scheduleFormValues(summary),
            running,
            deleting,
            taskForm,
            actions,
        };
    }, [schedule, canUpdate, canDelete, running, deleting, taskForm, actions]);
}

function useScheduleTasksModel(): ScheduleTasksModel {
    const { uuid } = useLoadedServer().attributes;
    const schedule = useLoadedSchedule();
    const [canUpdate] = usePermissions('schedule.update');
    const create = useCreateTask(schedule);
    const { mutateAsync: updateTask } = useUpdateServerScheduleTask(schedule);
    const { mutateAsync: deleteTask, isPending: deleting } = useDeleteServerScheduleTask(schedule);
    const form = useTaskForm();

    const actions = useMemo(
        () => ({
            create,
            update: (id: number, { timeOffset, ...values }: ScheduleTaskValues) =>
                attempt(() =>
                    updateTask(updateScheduleTaskInput(uuid, schedule, id, { ...values, timeOffset: timeOffset ?? 0 }))
                ),
            remove: (id: number) => attempt(() => deleteTask(deleteScheduleTaskInput(uuid, schedule, id))),
        }),
        [create, updateTask, deleteTask, uuid, schedule]
    );

    return useMemo(
        () => ({ tasks: orderedTasks(schedule), canUpdate, deleting, form, actions }),
        [schedule, canUpdate, deleting, form, actions]
    );
}

const resolved = async () => true;
const noop = () => {};

const scheduleFixture = (options: Partial<ScheduleSummary> = {}): ScheduleSummary => ({
    id: 1,
    name: 'Nightly restart',
    cron: { minute: '0', hour: '4', dayOfMonth: '*', month: '*', dayOfWeek: '*' },
    active: true,
    processing: false,
    onlyWhenOnline: true,
    lastRunAt: '2026-10-11T04:00:00Z',
    nextRunAt: '2026-10-12T04:00:00Z',
    ...options,
});

const scheduleFixtures: readonly ScheduleSummary[] = [
    scheduleFixture(),
    scheduleFixture({
        id: 2,
        name: 'Hourly backup',
        cron: { minute: '0', hour: '*/1', dayOfMonth: '*', month: '*', dayOfWeek: '*' },
        processing: true,
        lastRunAt: '2026-10-12T09:00:00Z',
        nextRunAt: '2026-10-12T10:00:00Z',
    }),
    scheduleFixture({
        id: 3,
        name: 'Weekend event',
        cron: { minute: '30', hour: '18', dayOfMonth: '*', month: '*', dayOfWeek: 'SAT,SUN' },
        active: false,
        lastRunAt: null,
        nextRunAt: null,
    }),
];

const listFixture = (options: Partial<ScheduleListModel> = {}): ScheduleListModel => ({
    serverId: 'a1b2c3d4',
    schedules: scheduleFixtures,
    loading: false,
    canCreate: true,
    canUpdate: true,
    defaults: newSchedule,
    create: resolved,
    ...options,
});

const taskFixtures: readonly ScheduleTask[] = [
    {
        id: 1,
        sequence: 1,
        action: 'command',
        payload: 'say Restarting in 5 minutes',
        timeOffset: 0,
        continueOnFailure: true,
    },
    { id: 2, sequence: 2, action: 'backup', payload: '', timeOffset: 240, continueOnFailure: false },
    { id: 3, sequence: 3, action: 'power', payload: 'restart', timeOffset: 60, continueOnFailure: false },
];

const scheduleModelFixture = (options: Partial<ScheduleModel> = {}): ScheduleModel => ({
    schedule: scheduleFixture(),
    taskCount: taskFixtures.length,
    canUpdate: true,
    canDelete: true,
    values: scheduleFormValues(scheduleFixture()),
    running: false,
    deleting: false,
    taskForm: scheduleTaskForm(3),
    actions: { update: resolved, run: resolved, remove: resolved, createTask: resolved },
    ...options,
});

const tasksFixture = (options: Partial<ScheduleTasksModel> = {}): ScheduleTasksModel => ({
    tasks: taskFixtures,
    canUpdate: true,
    deleting: false,
    form: scheduleTaskForm(3),
    actions: { create: resolved, update: resolved, remove: resolved },
    ...options,
});

implementViews({
    'page.server.schedules': {
        use: useSchedulesPageModel,
        fixtures: {
            default: () => ({ error: null, retry: noop }),
            error: () => ({ error: 'Request failed with status code 500.', retry: noop }),
        },
    },
    'server.schedules': {
        use: useScheduleListModel,
        fixtures: {
            schedules: () => listFixture(),
            empty: () => listFixture({ schedules: [] }),
            readOnly: () => listFixture({ canCreate: false, canUpdate: false }),
            loading: () => listFixture({ schedules: [], loading: true }),
        },
    },
    'page.server.schedules.detail': {
        use: useScheduleDetailPageModel,
        fixtures: {
            default: () => ({ loading: false, error: null, retry: noop }),
            loading: () => ({ loading: true, error: null, retry: noop }),
            error: () => ({ loading: false, error: 'Request failed with status code 404.', retry: noop }),
        },
    },
    'server.schedule': {
        use: useScheduleModel,
        fixtures: {
            active: () => scheduleModelFixture(),
            processing: () => scheduleModelFixture({ schedule: scheduleFixture({ processing: true }) }),
            inactive: () =>
                scheduleModelFixture({
                    schedule: scheduleFixture({ active: false, lastRunAt: null, nextRunAt: null }),
                    taskCount: 0,
                }),
            readOnly: () => scheduleModelFixture({ canUpdate: false, canDelete: false }),
        },
    },
    'server.schedule.tasks': {
        use: useScheduleTasksModel,
        fixtures: {
            tasks: () => tasksFixture(),
            empty: () => tasksFixture({ tasks: [] }),
            readOnly: () => tasksFixture({ canUpdate: false }),
            noBackups: () => tasksFixture({ form: scheduleTaskForm(0) }),
        },
    },
});
