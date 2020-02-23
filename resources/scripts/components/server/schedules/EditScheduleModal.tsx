import React from 'react';
import { Schedule } from '@/api/server/schedules/getServerSchedules';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import Field from '@/components/elements/Field';
import { connect } from 'react-redux';
import { Form, FormikProps, withFormik } from 'formik';
import { Actions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Switch from '@/components/elements/Switch';
import { boolean, object, string } from 'yup';

type OwnProps = { schedule: Schedule } & RequiredModalProps;

interface ReduxProps {
    addError: ApplicationStore['flashes']['addError'];
}

type ComponentProps = OwnProps & ReduxProps;

interface Values {
    name: string;
    dayOfWeek: string;
    dayOfMonth: string;
    hour: string;
    minute: string;
    enabled: boolean;
}

const EditScheduleModal = ({ values, schedule, ...props }: ComponentProps & FormikProps<Values>) => {
    return (
        <Modal {...props}>
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
                    <button className={'btn btn-lg btn-primary'} type={'button'}>
                        Save
                    </button>
                </div>
            </Form>
        </Modal>
    );
};

export default connect(
    null,
    // @ts-ignore
    (dispatch: Actions<ApplicationStore>) => ({
        addError: dispatch.flashes.addError,
    }),
)(
    withFormik<ComponentProps, Values>({
        handleSubmit: (values, { props }) => {
        },

        mapPropsToValues: ({ schedule }) => ({
            name: schedule.name,
            dayOfWeek: schedule.cron.dayOfWeek,
            dayOfMonth: schedule.cron.dayOfMonth,
            hour: schedule.cron.hour,
            minute: schedule.cron.minute,
            enabled: schedule.isActive,
        }),

        validationSchema: object().shape({
            name: string().required(),
            enabled: boolean().required(),
        }),
    })(EditScheduleModal),
);
