import { useState } from 'react';
import { type Schedule } from '@/api/server/schedules/queries';
import { useAppForm, Form } from '@/components/form';
import Switch from '@/components/ui/Switch';
import {
    createServerScheduleInput,
    updateServerScheduleInput,
    useCreateServerSchedule,
    useUpdateServerSchedule,
} from '@/api/server/schedules/queries';
import { Dialog, type DialogProps } from '@/components/elements/dialog';
import ScheduleCheatsheetCards from '@/components/server/schedules/ScheduleCheatsheetCards';
import { useCurrentServerUuid } from '@/api/server/queries';

interface Props {
    schedule?: Schedule;
}

const EditScheduleForm = ({ schedule, onClose }: Props & { onClose: () => void }) => {
    const uuid = useCurrentServerUuid()!;
    const createSchedule = useCreateServerSchedule();
    const updateSchedule = useUpdateServerSchedule();
    const [showCheatsheet, setShowCheetsheet] = useState(false);

    const form = useAppForm({
        defaultValues: {
            name: schedule?.attributes.name || '',
            minute: schedule?.attributes.cron.minute || '*/5',
            hour: schedule?.attributes.cron.hour || '*',
            dayOfMonth: schedule?.attributes.cron.day_of_month || '*',
            month: schedule?.attributes.cron.month || '*',
            dayOfWeek: schedule?.attributes.cron.day_of_week || '*',
            enabled: schedule?.attributes.is_active ?? true,
            onlyWhenOnline: schedule?.attributes.only_when_online ?? true,
        },
        onSubmit: async ({ value }) => {
            try {
                const values = {
                    name: value.name,
                    cron: {
                        minute: value.minute,
                        hour: value.hour,
                        dayOfWeek: value.dayOfWeek,
                        month: value.month,
                        dayOfMonth: value.dayOfMonth,
                    },
                    onlyWhenOnline: value.onlyWhenOnline,
                    isActive: value.enabled,
                };

                if (schedule) {
                    await updateSchedule.mutateAsync(updateServerScheduleInput(uuid, schedule.attributes.id, values));
                } else {
                    await createSchedule.mutateAsync(createServerScheduleInput(uuid, values));
                }
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    return (
        <Form form={form}>
            <form.AppField name={'name'}>
                {(field) => (
                    <field.TextField
                        label={'Schedule name'}
                        description={'A human readable identifier for this schedule.'}
                    />
                )}
            </form.AppField>
            <div
                className={
                    'mt-6 grid grid-cols-2 items-end gap-4 sm:grid-cols-5 ' +
                    '[&_label]:text-xs [&_label]:text-muted-foreground ' +
                    '[&_input]:text-center [&_input]:font-mono'
                }
            >
                <form.AppField name={'minute'}>{(field) => <field.TextField label={'Minute'} />}</form.AppField>
                <form.AppField name={'hour'}>{(field) => <field.TextField label={'Hour'} />}</form.AppField>
                <form.AppField name={'dayOfMonth'}>
                    {(field) => <field.TextField label={'Day of month'} />}
                </form.AppField>
                <form.AppField name={'month'}>{(field) => <field.TextField label={'Month'} />}</form.AppField>
                <form.AppField name={'dayOfWeek'}>{(field) => <field.TextField label={'Day of week'} />}</form.AppField>
            </div>
            <p className={'text-muted-foreground text-xs mt-2'}>
                The schedule system supports the use of Cronjob syntax when defining when tasks should begin running.
                Use the fields above to specify when these tasks should begin running.
            </p>
            <div className={'mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'}>
                <Switch
                    label={'Show Cheatsheet'}
                    description={'Show the cron cheatsheet for some examples.'}
                    checked={showCheatsheet}
                    onChange={() => setShowCheetsheet((s) => !s)}
                />
                {showCheatsheet && <ScheduleCheatsheetCards />}
            </div>
            <div className={'mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'}>
                <form.AppField name={'onlyWhenOnline'}>
                    {(field) => (
                        <field.SwitchField
                            description={'Only execute this schedule when the server is in a running state.'}
                            label={'Only When Server Is Online'}
                        />
                    )}
                </form.AppField>
            </div>
            <div className={'mt-6 bg-card border border-border shadow-inner p-4 rounded-sm'}>
                <form.AppField name={'enabled'}>
                    {(field) => (
                        <field.SwitchField
                            description={'This schedule will be executed automatically if enabled.'}
                            label={'Schedule Enabled'}
                        />
                    )}
                </form.AppField>
            </div>
            <div className={'mt-6 text-right'}>
                <form.AppForm>
                    <form.SubmitButton className={'w-full sm:w-auto'}>
                        {schedule ? 'Save changes' : 'Create schedule'}
                    </form.SubmitButton>
                </form.AppForm>
            </div>
        </Form>
    );
};

export default function EditScheduleModal({ open, onClose, ...props }: Props & DialogProps) {
    return (
        <Dialog open={open} title={props.schedule ? 'Edit schedule' : 'Create schedule'} onClose={onClose}>
            <EditScheduleForm {...props} onClose={onClose} />
        </Dialog>
    );
}
