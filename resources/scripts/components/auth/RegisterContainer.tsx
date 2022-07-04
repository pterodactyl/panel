import tw from 'twin.macro';
import Reaptcha from 'reaptcha';
import { object, string } from 'yup';
import useFlash from '@/plugins/useFlash';
import register from '@/api/auth/register';
import { useStoreState } from 'easy-peasy';
import { Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import React, { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/elements/button/index';
import { Link, RouteComponentProps } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import LoginFormContainer from '@/components/auth/LoginFormContainer';

interface Values {
    username: string;
    email: string;
    password: string;
}

const RegisterContainer = ({ history }: RouteComponentProps) => {
    const ref = useRef<Reaptcha>(null);
    const [token, setToken] = useState('');

    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const { enabled: recaptchaEnabled, siteKey } = useStoreState((state) => state.settings.data!.recaptcha);

    useEffect(() => {
        clearFlashes();
    }, []);

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();

        // If there is no token in the state yet, request the token and then abort this submit request
        // since it will be re-submitted when the recaptcha data is returned by the component.
        if (recaptchaEnabled && !token) {
            ref.current!.execute().catch((error) => {
                console.error(error);

                setSubmitting(false);
                clearAndAddHttpError({ error });
            });

            return;
        }

        register({ ...values, recaptchaData: token })
            .then((response) => {
                if (response.complete) {
                    history.replace('/auth/login');
                    addFlash({
                        key: 'auth:register',
                        type: 'success',
                        message: 'Account has been successfully created.',
                    });
                    return;
                }

                history.replace('/auth/register');
            })
            .catch((error) => {
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
            initialValues={{ username: '', email: '', password: '' }}
            validationSchema={object().shape({
                username: string().min(3).required(),
                email: string().email().required(),
                password: string().min(8).required(),
            })}
        >
            {({ isSubmitting, setSubmitting, submitForm }) => (
                <LoginFormContainer title={'Create an Account'} css={tw`w-full flex`}>
                    <FlashMessageRender byKey={'auth:register'} css={tw`my-3`} />
                    <Field type={'text'} label={'Username'} name={'username'} css={tw`my-3`} disabled={isSubmitting} />
                    <Field
                        type={'email'}
                        label={'Email Address'}
                        name={'email'}
                        css={tw`my-3`}
                        disabled={isSubmitting}
                    />
                    <Field
                        type={'password'}
                        label={'Password'}
                        name={'password'}
                        css={tw`my-3`}
                        disabled={isSubmitting}
                    />
                    <Button type={'submit'} css={tw`my-6 w-full`} size={Button.Sizes.Large} disabled={isSubmitting}>
                        Register
                    </Button>
                    {recaptchaEnabled && (
                        <Reaptcha
                            ref={ref}
                            size={'invisible'}
                            sitekey={siteKey || '_invalid_key'}
                            onVerify={(response) => {
                                setToken(response);
                                submitForm();
                            }}
                            onExpire={() => {
                                setSubmitting(false);
                                setToken('');
                            }}
                        />
                    )}
                    <div css={tw`text-center`}>
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
