import React, { useContext, useEffect } from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers } from 'formik';
import FormikSwitch from '@/components/elements/FormikSwitch';
import createOrUpdateSchedule from '@/api/server/schedules/createOrUpdateSchedule';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import ModalContext from '@/context/ModalContext';
import asModal from '@/hoc/asModal';
import { useTranslation } from 'react-i18next';

interface Props {
    schedule?: Schedule;
}

interface Values {
    name: string;
    dayOfWeek: string;
    month: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    enabled: boolean;
    onlyWhenOnline: boolean;
}

const EditScheduleModal = ({ schedule }: Props) => {
    const { t } = useTranslation();
    const { addError, clearFlashes } = useFlash();
    const { dismiss } = useContext(ModalContext);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);

    useEffect(() => {
        return () => {
            clearFlashes('schedule:edit');
        };
    }, []);

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
            onlyWhenOnline: values.onlyWhenOnline,
            isActive: values.enabled,
        })
            .then(schedule => {
                setSubmitting(false);
                appendSchedule(schedule);
                dismiss();
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
                enabled: schedule?.isActive ?? true,
                onlyWhenOnline: schedule?.onlyWhenOnline ?? true,
            } as Values}
        >
            {({ isSubmitting }) => (
                <Form>
                    <h3 css={tw`text-2xl mb-6`}>{schedule ? t('Schedule Edit Title') : t('Schedule Create Title')}</h3>
                    <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`}/>
                    <Field
                        name={'name'}
                        label={t('Schedule Name')}
                        description={t('Schedule Desc')}
                    />
                    <div css={tw`grid grid-cols-2 sm:grid-cols-5 gap-4 mt-6`}>
                        <Field name={'minute'} label={t('Schedule Minute')}/>
                        <Field name={'hour'} label={t('Schedule Hour')}/>
                        <Field name={'dayOfMonth'} label={t('Schedule Day Of Month')}/>
                        <Field name={'month'} label={t('Schedule Month')}/>
                        <Field name={'dayOfWeek'} label={t('Schedule Day Of Week')}/>
                    </div>
                    <p css={tw`text-neutral-400 text-xs mt-2`}>
                        {t('Schedule Description')}
                    </p>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'onlyWhenOnline'}
                            description={t('Schedule Option 1 Desc')}
                            label={t('Schedule Option 1')}
                        />
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'enabled'}
                            description={t('Schedule Option 2 Desc')}
                            label={t('Schedule Option 2')}
                        />
                    </div>
                    <div css={tw`mt-6 text-right`}>
                        <Button css={tw`w-full sm:w-auto`} type={'submit'} disabled={isSubmitting}>
                            {schedule ? t('Schedule Edit Button') : t('Schedule Create Button')}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(EditScheduleModal);
