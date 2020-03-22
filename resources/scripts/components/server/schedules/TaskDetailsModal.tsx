import React, { useEffect } from 'react';
import Modal from '@/components/elements/Modal';
import { Task } from '@/api/server/schedules/getServerSchedules';
import { Field as FormikField, Form, Formik, FormikHelpers, useFormikContext } from 'formik';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import createOrUpdateScheduleTask from '@/api/server/schedules/createOrUpdateScheduleTask';
import { httpErrorToHuman } from '@/api/http';
import Field from '@/components/elements/Field';
import FlashMessageRender from '@/components/FlashMessageRender';
import { number, object, string } from 'yup';

interface Props {
    scheduleId: number;
    // If a task is provided we can assume we're editing it. If not provided,
    // we are creating a new one.
    task?: Task;
    onDismissed: (task: Task | undefined | void) => void;
}

interface Values {
    action: string;
    payload: string;
    timeOffset: string;
}

const TaskDetailsForm = ({ isEditingTask }: { isEditingTask: boolean }) => {
    const { values: { action }, setFieldValue, setFieldTouched } = useFormikContext<Values>();

    useEffect(() => {
        setFieldValue('payload', '');
        setFieldTouched('payload', false);
    }, [action]);

    return (
        <Form className={'m-0'}>
            <h3 className={'mb-6'}>Edit Task</h3>
            <div className={'flex'}>
                <div className={'mr-2'}>
                    <label className={'input-dark-label'}>Action</label>
                    <FormikField as={'select'} name={'action'} className={'input-dark'}>
                        <option value={'command'}>Send command</option>
                        <option value={'power'}>Send power action</option>
                    </FormikField>
                </div>
                <div className={'flex-1'}>
                    {action === 'command' ?
                        <Field
                            name={'payload'}
                            label={'Payload'}
                            description={'The command to send to the server when this task executes.'}
                        />
                        :
                        <div>
                            <label className={'input-dark-label'}>Payload</label>
                            <FormikField as={'select'} name={'payload'} className={'input-dark'}>
                                <option value={'start'}>Start the server</option>
                                <option value={'restart'}>Restart the server</option>
                                <option value={'stop'}>Stop the server</option>
                                <option value={'kill'}>Terminate the server</option>
                            </FormikField>
                        </div>
                    }
                </div>
            </div>
            <div className={'mt-6'}>
                <Field
                    name={'timeOffset'}
                    label={'Time offset (in seconds)'}
                    description={'The amount of time to wait after the previous task executes before running this one. If this is the first task on a schedule this will not be applied.'}
                />
            </div>
            <div className={'flex justify-end mt-6'}>
                <button type={'submit'} className={'btn btn-primary btn-sm'}>
                    {isEditingTask ? 'Save Changes' : 'Create Task'}
                </button>
            </div>
        </Form>
    );
};

export default ({ task, scheduleId, onDismissed }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const { clearFlashes, addError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('schedule:task');
    }, []);

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('schedule:task');
        createOrUpdateScheduleTask(uuid, scheduleId, task?.id, values)
            .then(task => onDismissed(task))
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                addError({ message: httpErrorToHuman(error), key: 'schedule:task' });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                action: task?.action || 'command',
                payload: task?.payload || '',
                timeOffset: task?.timeOffset.toString() || '0',
            }}
            validationSchema={object().shape({
                action: string().required().oneOf([ 'command', 'power' ]),
                payload: string().required('A task payload must be provided.'),
                timeOffset: number().typeError('The time offset must be a valid number between 0 and 900.')
                    .required('A time offset value must be provided.')
                    .min(0, 'The time offset must be at least 0 seconds.')
                    .max(900, 'The time offset must be less than 900 seconds.'),
            })}
        >
            {({ isSubmitting }) => (
                <Modal
                    visible={true}
                    appear={true}
                    onDismissed={() => onDismissed()}
                    showSpinnerOverlay={isSubmitting}
                >
                    <FlashMessageRender byKey={'schedule:task'} className={'mb-4'}/>
                    <TaskDetailsForm isEditingTask={typeof task !== 'undefined'}/>
                </Modal>
            )}
        </Formik>
    );
};
