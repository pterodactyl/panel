import React, { useEffect, useRef, useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import register from '@/api/auth/register';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { useStoreState } from 'easy-peasy';
import { Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Reaptcha from 'reaptcha';
import useFlash from '@/plugins/useFlash';

interface Values {
    username: string;
    email: string;
    NameFirst: string;
    NameLast: string;
}

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<Reaptcha>(null);
    const [ token, setToken ] = useState('');

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState(state => state.settings.data!.recaptcha);

    useEffect(() => {
        clearFlashes();
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        // If there is no token in the state yet, request the token and then abort this submit request
        // since it will be re-submitted when the recaptcha data is returned by the component.
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch(error => {
                console.error(error);

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });

            return;
        }

        register({ ...values, recaptchaData: token })
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    alert('Your account has been registered on the Panel.\nCheck your email for login instructions.');
                    history.replace('/auth/login/');
                    return;
                }

                history.replace('/auth/register/');
            })
            .catch(error => {
                console.error(error);

                setToken('');
                if (ref.current) ref.current.reset();

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    };

    return (
        <Formik
            onSubmit={onSubmit}
            initialValues={{ username: '', email: '', NameFirst: '', NameLast: '' }}
            validationSchema={object().shape({
                username: string().required('A username must be provided.'),
                email: string().required('An email address must be provided.'),
                NameFirst: string().required('A first name must be provided.'),
                NameLast: string().required('A last name must be provided.'),
            })}
        >
            {({ isSubmitting, setSubmitting, submitForm }) => (
                <LoginFormContainer title={'Enter details to register'} css={tw`w-full flex`}>
                    <div css={tw`mt-6`}>
                        <Field
                            type={'text'}
                            label={'Username'}
                            name={'username'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>

                        <Field
                            type={'email'}
                            label={'Email Address'}
                            name={'email'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Field
                            type={'text'}
                            label={'First Name'}
                            name={'NameFirst'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Field
                            type={'text'}
                            label={'Last Name'}
                            name={'NameLast'}
                            disabled={isSubmitting}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting} disabled={isSubmitting}>
                            Register
                        </Button>
                    </div>
                    {recaptchaEnabled &&
                    <Reaptcha
                        ref={ref}
                        size={'invisible'}
                        sitekey={siteKey || '_invalid_key'}
                        onVerify={response => {
                            setToken(response);
                            submitForm();
                        }}
                        onExpire={() => {
                            setSubmitting(false);
                            setToken('');
                        }}
                    />
                    }
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                        >
                            Return to login
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default RegisterContainer;
