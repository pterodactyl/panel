import { useState } from 'react';
import {
    Dialog,
    Form,
    useAppForm,
    useTranslation,
    type ScheduleTask,
    type ScheduleTaskAction,
    type ScheduleTaskForm,
    type ScheduleTaskValues,
} from '@/sdk';

interface Props {
    open: boolean;
    onClose: () => void;
    form: ScheduleTaskForm;
    /** The task to edit; a new task when left out. */
    task?: ScheduleTask;
    onSubmit: (values: ScheduleTaskValues) => Promise<boolean>;
}

function TaskForm({ form: config, task, onSubmit, onClose }: Omit<Props, 'open'>) {
    const { t } = useTranslation();
    const [formError, setFormError] = useState<string | null>(null);
    const form = useAppForm({
        defaultValues: { ...config.values(task) },
        onSubmit: async ({ value }) => {
            setFormError(null);

            const error = config.validate(value);

            if (error) {
                setFormError(error);

                return;
            }

            if (value.timeOffset !== null && (await onSubmit(value))) {
                onClose();
            }
        },
    });
    const payloadValidators = {
        onChangeListenTo: ['action' as const],
        onChange: ({ value }: { value: string }) => config.validatePayload(form.getFieldValue('action'), value),
    };

    return (
        <Form form={form} className='m-0'>
            {formError && (
                <div className='rounded-sm border border-destructive/40 bg-destructive/10 p-3 text-sm text-foreground mb-4'>
                    {formError}
                </div>
            )}
            <div className='flex'>
                <div className='mr-2 w-1/3'>
                    <form.AppField name='action'>
                        {(field) => (
                            <field.SelectField
                                label={t('Action')}
                                options={[...config.actions]}
                                onChange={(value) =>
                                    form.setFieldValue('payload', config.payloadFor(value as ScheduleTaskAction, task))
                                }
                            />
                        )}
                    </form.AppField>
                </div>
                <div className='flex-1 ml-6'>
                    <form.AppField
                        name='timeOffset'
                        validators={{ onChange: ({ value }) => config.validateTimeOffset(value) }}
                    >
                        {(field) => (
                            <field.NumberField
                                label={t('Time offset (in seconds)')}
                                min={0}
                                max={900}
                                description={t(
                                    'The amount of time to wait after the previous task executes before running this one. If this is the first task on a schedule this will not be applied.'
                                )}
                            />
                        )}
                    </form.AppField>
                </div>
            </div>
            <div className='mt-6'>
                <form.Subscribe selector={(state) => state.values.action}>
                    {(action) => (
                        <>
                            {action === 'command' && (
                                <form.AppField name='payload' validators={payloadValidators}>
                                    {(field) => <field.TextAreaField label={t('Payload')} rows={6} />}
                                </form.AppField>
                            )}
                            {action === 'power' && (
                                <form.AppField name='payload' validators={payloadValidators}>
                                    {(field) => (
                                        <field.SelectField label={t('Payload')} options={[...config.powerSignals]} />
                                    )}
                                </form.AppField>
                            )}
                            {action === 'backup' && (
                                <form.AppField name='payload' validators={payloadValidators}>
                                    {(field) => (
                                        <field.TextAreaField
                                            label={t('Ignored Files')}
                                            rows={6}
                                            description={t(
                                                'Optional. Include the files and folders to be excluded in this backup. By default, the contents of your {{file}} file will be used. If you have reached your backup limit, the oldest backup will be rotated.',
                                                { file: '.pteroignore' }
                                            )}
                                        />
                                    )}
                                </form.AppField>
                            )}
                        </>
                    )}
                </form.Subscribe>
            </div>
            <div className='mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'>
                <form.AppField name='continueOnFailure'>
                    {(field) => (
                        <field.SwitchField
                            description={t('Future tasks will be run when this task fails.')}
                            label={t('Continue on Failure')}
                        />
                    )}
                </form.AppField>
            </div>
            <div className='flex justify-end mt-6'>
                <form.AppForm>
                    <form.SubmitButton>{task ? t('Save Changes') : t('Create Task')}</form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

/** Creates a task, or edits one: its action, delay, payload and failure behaviour. */
export default function TaskDialog({ open, onClose, ...props }: Props) {
    const { t } = useTranslation();

    return (
        <Dialog open={open} title={props.task ? t('Edit task') : t('Create task')} onClose={onClose}>
            <TaskForm key={props.task?.id ?? 'new'} {...props} onClose={onClose} />
        </Dialog>
    );
}
