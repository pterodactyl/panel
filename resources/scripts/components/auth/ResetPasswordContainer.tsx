import React, { useState } from 'react';
import { parse } from 'query-string';
import { Link } from 'react-router-dom';
import performPasswordReset from '@/api/auth/performPasswordReset';
import { httpErrorToHuman } from '@/api/http';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { Formik, FormikHelpers } from 'formik';
import { object, ref, string } from 'yup';
import Field from '@/components/elements/Field';
import Input from '@/components/elements/Input';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';
import { useParams } from 'react-router';

interface Values {
    password: string;
    passwordConfirmation: string;
}

const ResetPasswordContainer = ({ t }: WithTranslation) => {
    const [ email, setEmail ] = useState('');
    const { token } = useParams<{ token: string }>();
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const parsed = parse(location.search);
    if (email.length === 0 && parsed.email) {
        setEmail(parsed.email as string);
    }

    const submit = ({ password, passwordConfirmation }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes();
        performPasswordReset(email, { token, password, passwordConfirmation })
            .then(() => {
                // @ts-ignore
                window.location = '/';
            })
            .catch(error => {
                console.error(error);

                setSubmitting(false);
                addFlash({ type: 'error', title: t('elements:error'), message: httpErrorToHuman(error) });
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                password: '',
                passwordConfirmation: '',
            }}
            validationSchema={object().shape({
                password: string().required(t('auth:password.valid'))
                    .min(8, t('auth:password.valid')),
                passwordConfirmation: string()
                    .required(t('auth:password.confirm_failed'))
                    // @ts-ignore
                    .oneOf([ ref('password'), null ], t('auth:password.confirm_failed')),
            })}
        >
            {({ isSubmitting }) => (
                <LoginFormContainer
                    title={t('auth:password.reset.title')}
                    css={tw`w-full flex`}
                >
                    <div>
                        <label>{t('elements:email')}</label>
                        <Input value={email} isLight disabled/>
                    </div>
                    <div css={tw`mt-6`}>
                        <Field
                            light
                            label={t('auth:password.reset.new_password')}
                            name={'password'}
                            type={'password'}
                            description={t('auth:password.valid')}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Field
                            light
                            label={t('auth:password.confirm')}
                            name={'passwordConfirmation'}
                            type={'password'}
                        />
                    </div>
                    <div css={tw`mt-6`}>
                        <Button
                            size={'xlarge'}
                            type={'submit'}
                            disabled={isSubmitting}
                            isLoading={isSubmitting}
                        >
                            {t('auth:password.reset.title')}
                        </Button>
                    </div>
                    <div css={tw`mt-6 text-center`}>
                        <Link
                            to={'/auth/login'}
                            css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                        >
                            {t('auth:return_to_login')}
                        </Link>
                    </div>
                </LoginFormContainer>
            )}
        </Formik>
    );
};

export default withTranslation([ 'auth', 'elements' ])(ResetPasswordContainer);
