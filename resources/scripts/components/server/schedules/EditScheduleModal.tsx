import React, { useEffect, useState } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateSchedule from '@/api/server/schedules/createOrUpdateSchedule';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

type Props = {
    schedule?: Schedule;
} & RequiredModalProps;

interface Values {
    name: string;
    dayOfWeek: string;
    month: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    enabled: boolean;
}

const EditScheduleModal = ({ schedule, ...props }: Omit<Props, 'onScheduleUpdated'>) => {
    const { isSubmitting } = useFormikContext();

    return (
        <Modal {...props} showSpinnerOverlay={isSubmitting}>
            <h3 css={tw`text-2xl mb-6`}>{schedule ? 'Edit schedule' : 'Create new schedule'}</h3>
            <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`}/>
            <Form>
                <Field
                    name={'name'}
                    label={'Schedule name'}
                    description={'A human readable identifer for this schedule.'}
                />
                <div css={tw`grid grid-cols-2 sm:grid-cols-5 gap-4 mt-6`}>
                    <div>
                        <Field name={'minute'} label={'Minute'}/>
                    </div>
                    <div>
                        <Field name={'hour'} label={'Hour'}/>
                    </div>
                    <div>
                        <Field name={'dayOfMonth'} label={'Day of month'}/>
                    </div>
                    <div>
                        <Field name={'month'} label={'Month'}/>
                    </div>
                    <div>
                        <Field name={'dayOfWeek'} label={'Day of week'}/>
                    </div>
                </div>
                <p css={tw`text-neutral-400 text-xs mt-2`}>
                    The schedule system supports the use of Cronjob syntax when defining when tasks should begin
                    running. Use the fields above to specify when these tasks should begin running.
                </p>
                <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                    <FormikSwitch
                        name={'enabled'}
                        description={'If disabled, this schedule and it\'s associated tasks will not run.'}
                        label={'Enabled'}
                    />
                </div>
                <div css={tw`mt-6 text-right`}>
                    <Button css={tw`w-full sm:w-auto`} type={'submit'} disabled={isSubmitting}>
                        {schedule ? 'Save changes' : 'Create schedule'}
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default ({ schedule, visible, ...props }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { addError, clearFlashes } = useFlash();
    const [ modalVisible, setModalVisible ] = useState(visible);

    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    useEffect(() => {
        setModalVisible(visible);
        clearFlashes('schedule:edit');
    }, [ visible ]);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:edit');
        createOrUpdateSchedule(uuid, {
            id: schedule?.id,
            name: values.name,
            cron: {
                minute: values.minute,
                hour: values.hour,
                dayOfWeek: values.dayOfWeek,
                month: values.month,
                dayOfMonth: values.dayOfMonth,
            },
            isActive: values.enabled,
        })
            .then(schedule => {
                setSubmitting(false);
                appendSchedule(schedule);
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
                minute: schedule?.cron.minute || '*/5',
                hour: schedule?.cron.hour || '*',
                dayOfMonth: schedule?.cron.dayOfMonth || '*',
                month: schedule?.cron.month || '*',
                dayOfWeek: schedule?.cron.dayOfWeek || '*',
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
