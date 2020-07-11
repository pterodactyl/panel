import React, { useRef } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import login, { LoginData } from '@/api/auth/login';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { ActionCreator, Actions, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { FormikProps, withFormik } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import { httpErrorToHuman } from '@/api/http';
import { FlashMessage } from '@/state/flashes';
import ReCAPTCHA from 'react-google-recaptcha';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

type OwnProps = RouteComponentProps & {
    clearFlashes: ActionCreator<void>;
    addFlash: ActionCreator<FlashMessage>;
}

const LoginContainer = ({ isSubmitting, setFieldValue, values, submitForm, handleSubmit }: OwnProps & FormikProps<LoginData>) => {
    const ref = useRef<ReCAPTCHA | null>(null);
    const { enabled: recaptchaEnabled, siteKey } = useStoreState<ApplicationStore, any>(state => state.settings.data!.recaptcha);

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        if (ref.current && !values.recaptchaData) {
            return ref.current.execute();
        }

        handleSubmit(e);
    };

    return (
        <React.Fragment>
            {ref.current && ref.current.render()}
            <LoginFormContainer title={'Login to Continue'} css={tw`w-full flex`} onSubmit={submit}>
                <Field
                    type={'text'}
                    label={'Username or Email'}
                    id={'username'}
                    name={'username'}
                    light
                />
                <div css={tw`mt-6`}>
                    <Field
                        type={'password'}
                        label={'Password'}
                        id={'password'}
                        name={'password'}
                        light
                    />
                </div>
                <div css={tw`mt-6`}>
                    <Button type={'submit'} size={'xlarge'} isLoading={isSubmitting}>
                        Login
                    </Button>
                </div>
                {recaptchaEnabled &&
                <ReCAPTCHA
                    ref={ref}
                    size={'invisible'}
                    sitekey={siteKey || '_invalid_key'}
                    onChange={token => {
                        ref.current && ref.current.reset();
                        setFieldValue('recaptchaData', token);
                        submitForm();
                    }}
                    onExpired={() => setFieldValue('recaptchaData', null)}
                />
                }
                <div css={tw`mt-6 text-center`}>
                    <Link
                        to={'/auth/password'}
                        css={tw`text-xs text-neutral-500 tracking-wide no-underline uppercase hover:text-neutral-600`}
                    >
                        Forgot password?
                    </Link>
                </div>
            </LoginFormContainer>
        </React.Fragment>
    );
};

const EnhancedForm = withFormik<OwnProps, LoginData>({
    displayName: 'LoginContainerForm',

    mapPropsToValues: () => ({
        username: '',
        password: '',
        recaptchaData: null,
    }),

    validationSchema: () => object().shape({
        username: string().required('A username or email must be provided.'),
        password: string().required('Please enter your account password.'),
    }),

    handleSubmit: (values, { props, setFieldValue, setSubmitting }) => {
        props.clearFlashes();
        login(values)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                    return;
                }

                props.history.replace('/auth/login/checkpoint', { token: response.confirmationToken });
            })
            .catch(error => {
                console.error(error);

                setSubmitting(false);
                setFieldValue('recaptchaData', null);
                props.addFlash({ type: 'error', title: 'Error', message: httpErrorToHuman(error) });
            });
    },
})(LoginContainer);

export default (props: RouteComponentProps) => {
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    return (
        <EnhancedForm
            {...props}
            addFlash={addFlash}
            clearFlashes={clearFlashes}
        />
    );
};
