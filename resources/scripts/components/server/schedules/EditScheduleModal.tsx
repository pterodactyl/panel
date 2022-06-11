import React, { useContext, useEffect, useState } from 'react';
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
import Switch from '@/components/elements/Switch';
import ScheduleCheatsheetCards from '@/components/server/schedules/ScheduleCheatsheetCards';

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
    const { addError, clearFlashes } = useFlash();
    const { dismiss } = useContext(ModalContext);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const appendSchedule = ServerContext.useStoreActions(actions => actions.schedules.appendSchedule);
    const [ showCheatsheet, setShowCheetsheet ] = useState(false);

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
                    <h3 css={tw`text-2xl mb-6`}>{schedule ? '编辑计划' : '创建新计划'}</h3>
                    <FlashMessageRender byKey={'schedule:edit'} css={tw`mb-6`} />
                    <Field
                        name={'name'}
                        label={'计划名'}
                        description={'此计划的名字'}
                    />
                    <div css={tw`grid grid-cols-2 sm:grid-cols-5 gap-4 mt-6`}>
                        <Field name={'minute'} label={'分钟'} />
                        <Field name={'hour'} label={'小时'} />
                        <Field name={'dayOfMonth'} label={'每月的某一天'} />
                        <Field name={'month'} label={'月'} />
                        <Field name={'dayOfWeek'} label={'每周的某一天'} />
                    </div>
                    <p css={tw`text-neutral-400 text-xs mt-2`}>
                        计划系统支持在定义任务何时开始运行时使用 Cronjob 语法。 使用上面的字段来指定这些计划任务应该何时开始运行。
                    </p>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <Switch
                            name={'show_cheatsheet'}
                            description={'显示 cronjob 的一些例子'}
                            label={'显示例子'}
                            defaultChecked={showCheatsheet}
                            onChange={() => setShowCheetsheet(s => !s)}
                        />
                        {showCheatsheet &&
                            <div css={tw`block md:flex w-full`}>
                                <ScheduleCheatsheetCards/>
                            </div>
                        }
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'onlyWhenOnline'}
                            description={'仅在服务器处于运行状态时执行此计划。'}
                            label={'仅当服务器在线运行时'}
                        />
                    </div>
                    <div css={tw`mt-6 bg-neutral-700 border border-neutral-800 shadow-inner p-4 rounded`}>
                        <FormikSwitch
                            name={'enabled'}
                            description={'如果启用，此计划将自动执行。'}
                            label={'计划已启用'}
                        />
                    </div>
                    <div css={tw`mt-6 text-right`}>
                        <Button css={tw`w-full sm:w-auto`} type={'submit'} disabled={isSubmitting}>
                            {schedule ? '保存更改' : '创建计划'}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal<Props>()(EditScheduleModal);
