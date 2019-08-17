import React from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { Form, Formik, FormikActions } from 'formik';
import Field from '@/components/elements/Field';
import * as Yup from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import updateAccountPassword from '@/api/account/updateAccountPassword';
import { httpErrorToHuman } from '@/api/http';
import { ApplicationStore } from '@/state';

interface Values {
    current: string;
    password: string;
    confirmPassword: string;
}

const schema = Yup.object().shape({
    current: Yup.string().min(1).required('You must provide your current password.'),
    password: Yup.string().min(8).required(),
    confirmPassword: Yup.string().test('password', 'Password confirmation does not match the password you entered.', function (value) {
        return value === this.parent.password;
    }),
});

export default () => {
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    if (!user) {
        return null;
    }

    const submit = (values: Values, { resetForm, setSubmitting }: FormikActions<Values>) => {
        clearFlashes('account:password');
        updateAccountPassword({ ...values })
            .then(() => {
                resetForm();
                addFlash({ key: 'account:password', type: 'success', message: 'Your password has been updated.' });
            })
            .catch(error => addFlash({
                key: 'account:password',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            }))
            .then(() => setSubmitting(false));
    };

    return (
        <React.Fragment>
            <Formik
                onSubmit={submit}
                validationSchema={schema}
                initialValues={{ current: '', password: '', confirmPassword: '' }}
            >
                {
                    ({ isSubmitting, isValid }) => (
                        <React.Fragment>
                            <SpinnerOverlay size={'large'} visible={isSubmitting}/>
                            <Form className={'m-0'}>
                                <Field
                                    id={'current_password'}
                                    type={'password'}
                                    name={'current'}
                                    label={'Current Password'}
                                />
                                <div className={'mt-6'}>
                                    <Field
                                        id={'new_password'}
                                        type={'password'}
                                        name={'password'}
                                        label={'New Password'}
                                        description={'Your new password should be at least 8 characters in length and unique to this website.'}
                                    />
                                </div>
                                <div className={'mt-6'}>
                                    <Field
                                        id={'confirm_password'}
                                        type={'password'}
                                        name={'confirmPassword'}
                                        label={'Confirm New Password'}
                                    />
                                </div>
                                <div className={'mt-6'}>
                                    <button className={'btn btn-primary btn-sm'} disabled={isSubmitting || !isValid}>
                                        Update Password
                                    </button>
                                </div>
                            </Form>
                        </React.Fragment>
                    )
                }
            </Formik>
        </React.Fragment>
    );
};
