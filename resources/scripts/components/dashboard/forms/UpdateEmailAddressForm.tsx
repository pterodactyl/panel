import React from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { Form, Formik, FormikActions } from 'formik';
import * as Yup from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Field from '@/components/elements/Field';
import { httpErrorToHuman } from '@/api/http';
import { ApplicationStore } from '@/state';

interface Values {
    email: string;
    password: string;
}

const schema = Yup.object().shape({
    email: Yup.string().email().required(),
    password: Yup.string().required('You must provide your current account password.'),
});

export default () => {
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const updateEmail = useStoreActions((state: Actions<ApplicationStore>) => state.user.updateUserEmail);

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { resetForm, setSubmitting }: FormikActions<Values>) => {
        clearFlashes('account:email');

        updateEmail({ ...values })
            .then(() => addFlash({
                type: 'success',
                key: 'account:email',
                message: 'Your primary email has been updated.',
            }))
            .catch(error => addFlash({
                type: 'error',
                key: 'account:email',
                title: 'Error',
                message: httpErrorToHuman(error),
            }))
            .then(() => {
                resetForm();
                setSubmitting(false);
            });
    };

    return (
        <Formik
            onSubmit={submit}
            validationSchema={schema}
            initialValues={{ email: user!.email, password: '' }}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <SpinnerOverlay size={'large'} visible={isSubmitting}/>
                        <Form className={'m-0'}>
                            <Field
                                id={'current_email'}
                                type={'email'}
                                name={'email'}
                                label={'Email'}
                            />
                            <div className={'mt-6'}>
                                <Field
                                    id={'confirm_password'}
                                    type={'password'}
                                    name={'password'}
                                    label={'Confirm Password'}
                                />
                            </div>
                            <div className={'mt-6'}>
                                <button className={'btn btn-sm btn-primary'} disabled={isSubmitting || !isValid}>
                                    Update Email
                                </button>
                            </div>
                        </Form>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};
