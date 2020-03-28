import * as React from 'react';
import { Link } from 'react-router-dom';
import requestPasswordResetEmail from '@/api/auth/requestPasswordResetEmail';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Field from '@/components/elements/Field';
import { Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';

interface Values {
    email: string;
}

export default () => {
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const handleSubmission = ({ email }: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        setSubmitting(true);
        clearFlashes();
        requestPasswordResetEmail(email)
            .then(response => {
                resetForm();
                addFlash({ type: 'success', title: 'Success', message: response });
            })
            .catch(error => {
                console.error(error);
                addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            })
            .then(() => setSubmitting(false));
    };

    return (
        <Formik
            onSubmit={handleSubmission}
            initialValues={{ email: '' }}
            validationSchema={object().shape({
                email: string().email('A valid email address must be provided to continue.')
                    .required('A valid email address must be provided to continue.'),
            })}
        >
            {({ isSubmitting }) => (
                <LoginFormContainer
                    title={'Request Password Reset'}
                    className={'w-full flex'}
                >
                    <Field
                        light={true}
                        label={'Email'}
                        description={'Enter your account email address to receive instructions on resetting your password.'}
                        name={'email'}
                        type={'email'}
                    />
                    <div className={'mt-6'}>
                        <button
                            type={'submit'}
                            className={'btn btn-primary btn-jumbo flex justify-center'}
                            disabled={isSubmitting}
                        >
                            {isSubmitting ?
                                <div className={'spinner-circle spinner-sm spinner-white'}></div>
                                :
                                'Send Email'
                            }
                        </button>
                    </div>
                    <div className={'mt-6 text-center'}>
                        <Link
                            type={'button'}
                            to={'/auth/login'}
                            className={'text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700'}
                        >
                            Return to Login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};
