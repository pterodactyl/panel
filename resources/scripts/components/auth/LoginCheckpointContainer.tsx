import React, { useState } from 'react';
import { Link, RouteComponentProps } from 'react-router-dom';
import loginCheckpoint from '@/api/auth/loginCheckpoint';
import LoginFormContainer from '@/components/auth/LoginFormContainer';
import { ActionCreator } from 'easy-peasy';
import { StaticContext } from 'react-router';
import { useFormikContext, withFormik } from 'formik';
import useFlash from '@/plugins/useFlash';
import { FlashStore } from '@/state/flashes';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { WithTranslation, withTranslation } from 'react-i18next';

interface Values {
    code: string;
    recoveryCode: '',
}

type OwnProps = RouteComponentProps<Record<string, string | undefined>, StaticContext, { token?: string }>

type Props = OwnProps & WithTranslation & {
    clearAndAddHttpError: ActionCreator<FlashStore['clearAndAddHttpError']['payload']>;
}

const LoginCheckpointContainer = ({ t }: WithTranslation) => {
    const { isSubmitting, setFieldValue } = useFormikContext<Values>();
    const [ isMissingDevice, setIsMissingDevice ] = useState(false);

    return (
        <LoginFormContainer title={t('2fa_login_title')} css={tw`w-full flex`}>
            <div css={tw`mt-6`}>
                <Field
                    light
                    name={isMissingDevice ? 'recoveryCode' : 'code'}
                    title={isMissingDevice ? t('recovery_code') : t('authentication_code')}
                    description={
                        isMissingDevice
                            ? t('enter_recovery_code')
                            : t('enter_2fa_code')
                    }
                    type={'text'}
                    autoFocus
                />
            </div>
            <div css={tw`mt-6`}>
                <Button
                    size={'xlarge'}
                    type={'submit'}
                    disabled={isSubmitting}
                    isLoading={isSubmitting}
                >
                    {t('continue')}
                </Button>
            </div>
            <div css={tw`mt-6 text-center`}>
                <span
                    onClick={() => {
                        setFieldValue('code', '');
                        setFieldValue('recoveryCode', '');
                        setIsMissingDevice(s => !s);
                    }}
                    css={tw`cursor-pointer text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700`}
                >
                    {!isMissingDevice ? t('2fa_lost_device') : t('2fa_have_device')}
                </span>
            </div>
            <div css={tw`mt-6 text-center`}>
                <Link
                    to={'/auth/login'}
                    css={tw`text-xs text-neutral-500 tracking-wide uppercase no-underline hover:text-neutral-700`}
                >
                    {t('return_to_login')}
                </Link>
            </div>
        </LoginFormContainer>
    );
};

const EnhancedForm = withFormik<Props, Values>({
    handleSubmit: ({ code, recoveryCode }, { setSubmitting, props: { clearAndAddHttpError, location } }) => {
        loginCheckpoint(location.state?.token || '', code, recoveryCode)
            .then(response => {
                if (response.complete) {
                    // @ts-ignore
                    window.location = response.intended || '/';
                    return;
                }

                setSubmitting(false);
            })
            .catch(error => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ error });
            });
    },

    mapPropsToValues: () => ({
        code: '',
        recoveryCode: '',
    }),
})(LoginCheckpointContainer);

export default withTranslation('auth')(({ history, location, ...props }: OwnProps & WithTranslation) => {
    const { clearAndAddHttpError } = useFlash();

    if (!location.state?.token) {
        history.replace('/auth/login');

        return null;
    }

    return <EnhancedForm
        clearAndAddHttpError={clearAndAddHttpError}
        history={history}
        location={location}
        {...props}
    />;
});
