import { useState } from 'react';
import { Dialog, Form, Switch, useAppForm, type ScheduleFormValues } from '@/sdk';
import ScheduleCheatsheet from './ScheduleCheatsheet';

interface Props {
    open: boolean;
    onClose: () => void;
    /** The values the form starts from. */
    values: ScheduleFormValues;
    /** The form edits an existing schedule rather than creating one. */
    editing: boolean;
    onSubmit: (values: ScheduleFormValues) => Promise<boolean>;
}

const cronFieldsClass = [
    'mt-6 grid grid-cols-2 items-end gap-4 sm:grid-cols-5',
    '[&_label]:text-xs [&_label]:text-muted-foreground',
    '[&_input]:text-center [&_input]:font-mono',
].join(' ');

function ScheduleForm({ values, editing, onSubmit, onClose }: Omit<Props, 'open'>) {
    const [showCheatsheet, setShowCheatsheet] = useState(false);
    const form = useAppForm({
        defaultValues: { ...values },
        onSubmit: async ({ value }) => {
            if (await onSubmit(value)) {
                onClose();
            }
        },
    });

    return (
        <Form form={form}>
            <form.AppField name='name'>
                {(field) => (
                    <field.TextField
                        label='Schedule name'
                        description='A human readable identifier for this schedule.'
                    />
                )}
            </form.AppField>
            <div className={cronFieldsClass}>
                <form.AppField name='minute'>{(field) => <field.TextField label='Minute' />}</form.AppField>
                <form.AppField name='hour'>{(field) => <field.TextField label='Hour' />}</form.AppField>
                <form.AppField name='dayOfMonth'>{(field) => <field.TextField label='Day of month' />}</form.AppField>
                <form.AppField name='month'>{(field) => <field.TextField label='Month' />}</form.AppField>
                <form.AppField name='dayOfWeek'>{(field) => <field.TextField label='Day of week' />}</form.AppField>
            </div>
            <p className='text-muted-foreground text-xs mt-2'>
                The schedule system supports the use of Cronjob syntax when defining when tasks should begin running.
                Use the fields above to specify when these tasks should begin running.
            </p>
            <div className='mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'>
                <Switch
                    label='Show Cheatsheet'
                    description='Show the cron cheatsheet for some examples.'
                    checked={showCheatsheet}
                    onChange={() => setShowCheatsheet((shown) => !shown)}
                />
                {showCheatsheet && <ScheduleCheatsheet />}
            </div>
            <div className='mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'>
                <form.AppField name='onlyWhenOnline'>
                    {(field) => (
                        <field.SwitchField
                            description='Only execute this schedule when the server is in a running state.'
                            label='Only When Server Is Online'
                        />
                    )}
                </form.AppField>
            </div>
            <div className='mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'>
                <form.AppField name='enabled'>
                    {(field) => (
                        <field.SwitchField
                            description='This schedule will be executed automatically if enabled.'
                            label='Schedule Enabled'
                        />
                    )}
                </form.AppField>
            </div>
            <div className='mt-6 text-right'>
                <form.AppForm>
                    <form.SubmitButton className='w-full sm:w-auto'>
                        {editing ? 'Save changes' : 'Create schedule'}
                    </form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
}

/** Creates a schedule, or edits one, with its name, cron fields and switches. */
export default function ScheduleDialog({ open, onClose, ...props }: Props) {
    return (
        <Dialog open={open} title={props.editing ? 'Edit schedule' : 'Create schedule'} onClose={onClose}>
            <ScheduleForm {...props} onClose={onClose} />
        </Dialog>
    );
}
