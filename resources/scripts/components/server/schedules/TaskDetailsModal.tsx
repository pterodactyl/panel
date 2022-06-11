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

const schema = object().shape({
    action: string().required().oneOf([ 'command', 'power', 'backup' ]),
    payload: string().when('action', {
        is: v => v !== 'backup',
        then: string().required('必须提供有效的任务操作。'),
        otherwise: string(),
    }),
    continueOnFailure: boolean(),
    timeOffset: number().typeError('时间偏移必须是 0 到 900 之间的有效数字。')
        .required('必须提供时间偏移值。')
        .min(0, '时间偏移至少为 0 秒。')
        .max(900, '时间偏移必须小于 900 秒。'),
});

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
            addError({ message: '当服务器的备份限制设置为 0 时，无法创建备份任务。', key: 'schedule:task' });
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
                    <h2 css={tw`text-2xl mb-6`}>{task ? '编辑任务' : '创建任务'}</h2>
                    <div css={tw`flex`}>
                        <div css={tw`mr-2 w-1/3`}>
                            <Label>Action</Label>
                            <ActionListener/>
                            <FormikFieldWrapper name={'action'}>
                                <FormikField as={Select} name={'action'}>
                                    <option value={'command'}>发送指令</option>
                                    <option value={'power'}>发送电源操作</option>
                                    <option value={'backup'}>创建备份</option>
                                </FormikField>
                            </FormikFieldWrapper>
                        </div>
                        <div css={tw`flex-1 ml-6`}>
                            <Field
                                name={'timeOffset'}
                                label={'时间偏移（以秒为单位）'}
                                description={'上一个任务执行后在运行此任务之前等待的时间。 如果这是计划中的第一个任务，则不会应用该任务。'}
                            />
                        </div>
                    </div>
                    <div css={tw`mt-6`}>
                        {values.action === 'command' ?
                            <div>
                                <Label>任务操作</Label>
                                <FormikFieldWrapper name={'payload'}>
                                    <FormikField as={Textarea} name={'payload'} rows={6}/>
                                </FormikFieldWrapper>
                            </div>
                            :
                            values.action === 'power' ?
                                <div>
                                    <Label>任务操作</Label>
                                    <FormikFieldWrapper name={'payload'}>
                                        <FormikField as={Select} name={'payload'}>
                                            <option value={'start'}>启动服务器实例</option>
                                            <option value={'restart'}>重启服务器实例</option>
                                            <option value={'stop'}>关闭服务器实例</option>
                                            <option value={'kill'}>停止服务器实例</option>
                                        </FormikField>
                                    </FormikFieldWrapper>
                                </div>
                                :
                                <div>
                                    <Label>Ignored Files</Label>
                                    <FormikFieldWrapper
                                        name={'payload'}
                                        description={'可选的。 包括要在此备份中排除的文件和文件夹。 默认情况下，将使用 .pteroignore 文件的内容。 如果您已达到备份限制，则将轮换最早的备份。'}
                                    >
                                        <FormikField as={Textarea} name={'payload'} rows={6}/>
                                    </FormikFieldWrapper>
                                </div>
                        }
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'continueOnFailure'}
                            description={'当此任务失败时，将运行未来的任务。'}
                            label={'即使失败也继续执行'}
                        />
                    </div>
                    <div css={tw`flex justify-end mt-6`}>
                        <Button type={'submit'} disabled={isSubmitting}>
                            {task ? '保存更改' : '创建任务'}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(TaskDetailsModal);
