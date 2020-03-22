import React, { useEffect, useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import Switch from '@/components/elements/Switch';
import createOrUpdateSchedule from '@/api/server/schedules/createOrUpdateSchedule';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';

type Props = {
    schedule?: Schedule;
    onScheduleUpdated: (schedule: Schedule) => void;
} & RequiredModalProps;

interface Values {
    name: string;
    dayOfWeek: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    enabled: boolean;
}

const EditScheduleModal = ({ schedule, ...props }: Omit<Props, 'onScheduleUpdated'>) => {
    const { isSubmitting } = useFormikContext();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <h3 className={'mb-6'}>{schedule ? 'Edit schedule' : 'Create new schedule'}</h3>
            <FlashMessageRender byKey={'schedule:edit'} className={'mb-6'}/>
            <Form>
                <Field
                    name={'name'}
                    label={'Schedule name'}
                    description={'A human readable identifer for this schedule.'}
                />
                <div className={'flex mt-6'}>
                    <div className={'flex-1 mr-4'}>
                        <Field name={'dayOfWeek'} label={'Day of week'}/>
                    </div>
                    <div className={'flex-1 mr-4'}>
                        <Field name={'dayOfMonth'} label={'Day of month'}/>
                    </div>
                    <div className={'flex-1 mr-4'}>
                        <Field name={'hour'} label={'Hour'}/>
                    </div>
                    <div className={'flex-1'}>
                        <Field name={'minute'} label={'Minute'}/>
                    </div>
                </div>
                <p className={'input-help'}>
                    The schedule system supports the use of Cronjob syntax when defining when tasks should begin
                    running. Use the fields above to specify when these tasks should begin running.
                </p>
                <div className={'mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded'}>
                    <Switch
                        name={'enabled'}
                        description={'If disabled, this schedule and it\'s associated tasks will not run.'}
                        label={'Enabled'}
                    />
                </div>
                <div className={'mt-6 text-right'}>
                    <button className={'btn btn-sm btn-primary'} type={'submit'}>
                        {schedule ? 'Save changes' : 'Create schedule'}
                    </button>
                </div>
            </Form>
        </Modal>
    );
};

export default ({ schedule, onScheduleUpdated, visible, ...props }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ modalVisible, setModalVisible ] = useState(visible);

    useEffect(() => {
        setModalVisible(visible);
        clearFlashes('schedule:edit');
    }, [visible]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:edit');
        createOrUpdateSchedule(uuid, {
            id: schedule?.id,
            name: values.name,
            cron: {
                minute: values.minute,
                hour: values.hour,
                dayOfWeek: values.dayOfWeek,
                dayOfMonth: values.dayOfMonth,
            },
            isActive: values.enabled,
        })
            .then(schedule => {
                setSubmitting(false);
                onScheduleUpdated(schedule);
                setModalVisible(false);
            })
            .catch(error => {
                console.error(error);

                setSubmitting(false);
                addError({ key: 'schedule:edit', message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                name: schedule?.name || '',
                dayOfWeek: schedule?.cron.dayOfWeek || '*',
                dayOfMonth: schedule?.cron.dayOfMonth || '*',
                hour: schedule?.cron.hour || '*',
                minute: schedule?.cron.minute || '*/5',
                enabled: schedule ? schedule.isActive : true,
            } as Values}
            validationSchema={null}
        >
            <EditScheduleModal
                visible={modalVisible}
                schedule={schedule}
                {...props}
            />
        </Formik>
    );
};
