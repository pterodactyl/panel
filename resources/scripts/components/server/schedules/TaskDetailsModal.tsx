import { useState } from 'react';
import { type Schedule, type Task } from '@/api/server/schedules/queries';
import { useStore } from '@tanstack/react-form';
import { useAppForm, Form } from '@/components/form';
import {
    createScheduleTaskInput,
    updateScheduleTaskInput,
    useCreateServerScheduleTask,
    useUpdateServerScheduleTask,
} from '@/api/server/schedules/queries';
import { Dialog, type DialogProps } from '@/components/elements/dialog';
import { useCurrentServer } from '@/api/server/queries';

interface Props {
    schedule: Schedule;
    task?: Task;
}

type Action = 'command' | 'power' | 'backup';

interface TaskFormValues {
    action: Action;
    payload: string;
    timeOffset: number | null;
    continueOnFailure: boolean;
}

const TaskDetailsForm = ({ schedule, task, onClose }: Props & { onClose: () => void }) => {
    const [formError, setFormError] = useState<string | null>(null);

    const server = useCurrentServer()!;
    const uuid = server.attributes.uuid;
    const createTask = useCreateServerScheduleTask(schedule);
    const updateTask = useUpdateServerScheduleTask(schedule);
    const backupLimit = server.attributes.feature_limits.backups;

    const initialAction = (task?.attributes.action || 'command') as Action;
    const initialPayload = task?.attributes.payload || '';

    const defaultValues: TaskFormValues = {
        action: initialAction,
        payload: initialPayload,
        timeOffset: task?.attributes.time_offset ?? 0,
        continueOnFailure: task?.attributes.continue_on_failure || false,
    };

    const form = useAppForm({
        defaultValues,
        onSubmit: async ({ value }) => {
            setFormError(null);
            if (backupLimit === 0 && value.action === 'backup') {
                setFormError("A backup task cannot be created when the server's backup limit is set to 0.");
                return;
            }

            const { timeOffset } = value;
            if (timeOffset === null) {
                return;
            }

            const values = { ...value, timeOffset };

            try {
                if (task) {
                    await updateTask.mutateAsync(updateScheduleTaskInput(uuid, schedule, task.attributes.id, values));
                } else {
                    await createTask.mutateAsync(createScheduleTaskInput(uuid, schedule, values));
                }
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const action = useStore(form.store, (state) => state.values.action);
    const payloadValidators = {
        onChangeListenTo: ['action' as const],
        onChange: ({ value }: { value: string }) =>
            form.getFieldValue('action') !== 'command' || value.length > 0
                ? undefined
                : 'A task payload must be provided.',
    };
    const setPayloadForAction = (nextAction: Action) => {
        form.setFieldValue(
            'payload',
            nextAction === initialAction ? initialPayload || '' : nextAction === 'power' ? 'start' : ''
        );
    };

    return (
        <Form form={form} className={'m-0'}>
            {formError && (
                <div
                    className={
                        'rounded-sm border border-destructive/40 bg-destructive/10 p-3 text-sm text-foreground mb-4'
                    }
                >
                    {formError}
                </div>
            )}
            <div className={'flex'}>
                <div className={'mr-2 w-1/3'}>
                    <form.AppField name={'action'}>
                        {(field) => (
                            <field.SelectField
                                label={'Action'}
                                options={[
                                    { value: 'command', label: 'Send command' },
                                    { value: 'power', label: 'Send power action' },
                                    { value: 'backup', label: 'Create backup' },
                                ]}
                                onChange={(value) => setPayloadForAction(value as Action)}
                            />
                        )}
                    </form.AppField>
                </div>
                <div className={'flex-1 ml-6'}>
                    <form.AppField
                        name={'timeOffset'}
                        validators={{
                            onChange: ({ value }) =>
                                value === null
                                    ? 'A time offset must be provided.'
                                    : value < 0
                                      ? 'The time offset must be at least 0 seconds.'
                                      : value > 900
                                        ? 'The time offset must be less than 900 seconds.'
                                        : undefined,
                        }}
                    >
                        {(field) => (
                            <field.NumberField
                                label={'Time offset (in seconds)'}
                                min={0}
                                max={900}
                                description={
                                    'The amount of time to wait after the previous task executes before running this one. If this is the first task on a schedule this will not be applied.'
                                }
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
            <div className={'mt-6'}>
                {action === 'command' ? (
                    <form.AppField name={'payload'} validators={payloadValidators}>
                        {(field) => <field.TextAreaField label={'Payload'} rows={6} />}
                    </form.AppField>
                ) : action === 'power' ? (
                    <form.AppField name={'payload'} validators={payloadValidators}>
                        {(field) => (
                            <field.SelectField
                                label={'Payload'}
                                options={[
                                    { value: 'start', label: 'Start the server' },
                                    { value: 'restart', label: 'Restart the server' },
                                    { value: 'stop', label: 'Stop the server' },
                                    { value: 'kill', label: 'Terminate the server' },
                                ]}
                            />
                        )}
                    </form.AppField>
                ) : (
                    <form.AppField name={'payload'} validators={payloadValidators}>
                        {(field) => (
                            <field.TextAreaField
                                label={'Ignored Files'}
                                rows={6}
                                description={
                                    'Optional. Include the files and folders to be excluded in this backup. By default, the contents of your .pteroignore file will be used. If you have reached your backup limit, the oldest backup will be rotated.'
                                }
                            />
                        )}
                    </form.AppField>
                )}
            </div>
            <div className={'mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'}>
                <form.AppField name={'continueOnFailure'}>
                    {(field) => (
                        <field.SwitchField
                            description={'Future tasks will be run when this task fails.'}
                            label={'Continue on Failure'}
                        />
                    )}
                </form.AppField>
            </div>
            <div className={'flex justify-end mt-6'}>
                <form.AppForm>
                    <form.SubmitButton>{task ? 'Save Changes' : 'Create Task'}</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
};

export default function TaskDetailsModal({ open, onClose, ...props }: Props & DialogProps) {
    const formKey = `${props.schedule.attributes.id}:${props.task?.attributes.id ?? 'new'}`;

    return (
        <Dialog open={open} title={props.task ? 'Edit task' : 'Create task'} onClose={onClose}>
            <TaskDetailsForm key={formKey} {...props} onClose={onClose} />
        </Dialog>
    );
}
