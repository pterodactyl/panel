import React, { useContext, useEffect } from 'react';
import { Schedule, Task } from '@/api/server/schedules/getServerSchedules';
import { Field as FormikField, Form, Formik, FormikHelpers, useField } from 'formik';
import { ServerContext } from '@/state/server';
import createOrUpdateScheduleTask from '@/api/server/schedules/createOrUpdateScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import Field from '@/components/elements/Field';
import FlashMessageRender from '@/components/FlashMessageRender';
import { boolean, number, object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import tw from 'twin.macro';
import Label from '@/components/elements/Label';
import { Textarea } from '@/components/elements/Input';
import Button from '@/components/elements/Button';
import Select from '@/components/elements/Select';
import ModalContext from '@/context/ModalContext';
import asModal from '@/hoc/asModal';
import FormikSwitch from '@/components/elements/FormikSwitch';
import { useTranslation } from 'react-i18next';

interface Props {
    schedule: Schedule;
    // If a task is provided we can assume we're editing it. If not provided,
    // we are creating a new one.
    task?: Task;
}

interface Values {
    action: string;
    payload: string;
    timeOffset: string;
    continueOnFailure: boolean;
}


const ActionListener = () => {
    const [ { value }, { initialValue: initialAction } ] = useField<string>('action');
    const [ , { initialValue: initialPayload }, { setValue, setTouched } ] = useField<string>('payload');

    useEffect(() => {
        if (value !== initialAction) {
            setValue(value === 'power' ? 'start' : '');
            setTouched(false);
        } else {
            setValue(initialPayload || '');
            setTouched(false);
        }
    }, [ value ]);

    return null;
};

const TaskDetailsModal = ({ schedule, task }: Props) => {
    const { t } = useTranslation();
    const { dismiss } = useContext(ModalContext);
    const { clearFlashes, addError } = useFlash();

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);
    const backupLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.backups);

    useEffect(() => {
        return () => {
            clearFlashes('schedule:task');
        };
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:task');
        if (backupLimit === 0 && values.action === 'backup') {
            setSubmitting(false);
            addError({ message: t('Schedule Create Backup Error'), key: 'schedule:task' });
        } else {
            createOrUpdateScheduleTask(uuid, schedule.id, task?.id, values)
                .then(task => {
                    let tasks = schedule.tasks.map(t => t.id === task.id ? task : t);
                    if (!schedule.tasks.find(t => t.id === task.id)) {
                        tasks = [ ...tasks, task ];
                    }

                    appendSchedule({ ...schedule, tasks });
                    dismiss();
                })
                .catch(error => {
                    console.error(error);
                    setSubmitting(false);
                    addError({ message: httpErrorToHuman(error), key: 'schedule:task' });
                });
        }
    };

    const schema = object().shape({
        action: string().required().oneOf([ 'command', 'power', 'backup' ]),
        payload: string().when('action', {
            is: v => v !== 'backup',
            then: string().required(t('Schedule Edit Task Warning')),
            otherwise: string(),
        }),
        continueOnFailure: boolean(),
        timeOffset: number().typeError('Schedule Warning Message 1')
            .required(t('Schedule Warning Message 2'))
            .min(0, t('Schedule Warning Message 3'))
            .max(900, t('Schedule Warning Message 4')),
    });

    return (
        <Formik
            onSubmit={submit}
            validationSchema={schema}
            initialValues={{
                action: task?.action || 'command',
                payload: task?.payload || '',
                timeOffset: task?.timeOffset.toString() || '0',
                continueOnFailure: task?.continueOnFailure || false,
            }}
        >
            {({ isSubmitting, values }) => (
                <Form css={tw`m-0`}>
                    <FlashMessageRender byKey={'schedule:task'} css={tw`mb-4`}/>
                    <h2 css={tw`text-2xl mb-6`}>{task ? t('Schedule Edit Task Title') : t('Schedule Create Task Title')}</h2>
                    <div css={tw`flex`}>
                        <div css={tw`mr-2 w-1/3`}>
                            <Label>{t('Schedule Select Options Title')}</Label>
                            <ActionListener/>
                            <FormikFieldWrapper name={'action'}>
                                <FormikField as={Select} name={'action'}>
                                    <option value={'command'}>{t('Schedule Send Command Option')}</option>
                                    <option value={'power'}>{t('Schedule Send Power Option')}</option>
                                    <option value={'backup'}>{t('Schedule Create Backup Option')}</option>
                                </FormikField>
                            </FormikFieldWrapper>
                        </div>
                        <div css={tw`flex-1 ml-6`}>
                            <Field
                                name={'timeOffset'}
                                label={t('Schedule Time Title')}
                                description={t('Schedule Time Desc')}
                            />
                        </div>
                    </div>
                    <div css={tw`mt-6`}>
                        {values.action === 'command' ?
                            <div>
                                <Label>{t('Schedule Payload Title')}</Label>
                                <FormikFieldWrapper name={'payload'}>
                                    <FormikField as={Textarea} name={'payload'} rows={6}/>
                                </FormikFieldWrapper>
                            </div>
                            :
                            values.action === 'power' ?
                                <div>
                                    <Label>{t('Schedule Payload Title')}</Label>
                                    <FormikFieldWrapper name={'payload'}>
                                        <FormikField as={Select} name={'payload'}>
                                            <option value={'Start'}>{t('Schedule Payload Option 1')}</option>
                                            <option value={'Restart'}>{t('Schedule Payload Option 2')}</option>
                                            <option value={'Stop'}>{t('Schedule Payload Option 3')}</option>
                                            <option value={'Kill'}>{t('Schedule Payload Option 4')}</option>
                                        </FormikField>
                                    </FormikFieldWrapper>
                                </div>
                                :
                                <div>
                                    <Label>{t('Schedule Files Desc Title')}</Label>
                                    <FormikFieldWrapper
                                        name={'payload'}
                                        description={t('Schedule Files Desc')}
                                    >
                                        <FormikField as={Textarea} name={'payload'} rows={6}/>
                                    </FormikFieldWrapper>
                                </div>
                        }
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'continueOnFailure'}
                            description={t('Schedule Failure Button Desc')}
                            label={t('Schedule Failure Button')}
                        />
                    </div>
                    <div css={tw`flex justify-end mt-6`}>
                        <Button type={'submit'} disabled={isSubmitting}>
                            {task ? t('Schedule Button Edit Task') : t('Schedule Button Create Task')}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(TaskDetailsModal);
