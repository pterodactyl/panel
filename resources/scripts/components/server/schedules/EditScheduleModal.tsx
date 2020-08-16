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
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

type Props = {
    schedule?: Schedule;
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
            <h3 css={tw`mb-6`}>{schedule ? 'Edit schedule' : 'Create new schedule'}</h3>
            <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`}/>
            <Form>
                <Field
                    name={'name'}
                    label={'Schedule name'}
                    description={'A human readable identifer for this schedule.'}
                />
                <div css={tw`flex mt-6`}>
                    <div css={tw`flex-1 mr-4`}>
                        <Field name={'dayOfWeek'} label={'Day of week'}/>
                    </div>
                    <div css={tw`flex-1 mr-4`}>
                        <Field name={'dayOfMonth'} label={'Day of month'}/>
                    </div>
                    <div css={tw`flex-1 mr-4`}>
                        <Field name={'hour'} label={'Hour'}/>
                    </div>
                    <div css={tw`flex-1`}>
                        <Field name={'minute'} label={'Minute'}/>
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
                    <Button type={'submit'} disabled={isSubmitting}>
                        {schedule ? 'Save changes' : 'Create schedule'}
                    </Button>
                </div>
            </Form>
        </Modal>
    );
};

export default ({ schedule, visible, ...props }: Props) => {
    const { uuid } = useServer();
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
