import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import getTwoFactorTokenUrl from '@/api/account/getTwoFactorTokenUrl';
import enableAccountTwoFactor from '@/api/account/enableAccountTwoFactor';
import { useStoreActions } from '@/state/hooks';
import FlashMessageRender from '@/components/FlashMessageRender';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { Trans, WithTranslation, withTranslation } from 'react-i18next';

interface Values {
    code: string;
}

const SetupTwoFactorModal = ({ t, onDismissed, ...props }: RequiredModalProps & WithTranslation) => {
    const [ token, setToken ] = useState('');
    const [ loading, setLoading ] = useState(true);
    const [ recoveryTokens, setRecoveryTokens ] = useState<string[]>([]);

    const updateUserData = useStoreActions(actions => actions.user.updateUserData);
    const clearAndAddHttpError = useStoreActions(actions => actions.flashes.clearAndAddHttpError);

    useEffect(() => {
        getTwoFactorTokenUrl()
            .then(setToken)
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: 'account:two-factor' });
            });
    }, []);

    const submit = ({ code }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        enableAccountTwoFactor(code)
            .then(tokens => {
                setRecoveryTokens(tokens);
            })
            .catch(error => {
                console.error(error);

                clearAndAddHttpError({ error, key: 'account:two-factor' });
            })
            .then(() => setSubmitting(false));
    };

    const dismiss = () => {
        if (recoveryTokens.length > 0) {
            updateUserData({ useTotp: true });
        }

        onDismissed();
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ code: '' }}
            validationSchema={object().shape({
                code: string()
                    .required(t('dashboard:2fa.validation.code_required'))
                    .matches(/^(\d){6}$/, t('dashboard:2fa.validation.code_length')),
            })}
        >
            {({ isSubmitting }) => (
                <Modal
                    {...props}
                    top={false}
                    onDismissed={dismiss}
                    dismissable={!isSubmitting}
                    showSpinnerOverlay={loading || isSubmitting}
                    closeOnEscape={!recoveryTokens}
                    closeOnBackground={!recoveryTokens}
                >
                    {recoveryTokens.length > 0 ?
                        <>
                            <h2 css={tw`text-2xl mb-4`}>{t('dashboard:2fa.setup.enabled_title')}</h2>
                            <p css={tw`text-neutral-300`}>
                                {t('dashboard:2fa.setup.enabled_desc')}
                            </p>
                            <p css={tw`text-neutral-300 mt-4`}>
                                <Trans i18nKey={'2fa.setup.store_securely'} components={{ bold: <strong/> }} ns={'dashboard'}/>
                            </p>
                            <pre css={tw`text-sm mt-4 rounded font-mono bg-neutral-900 p-4`}>
                                {recoveryTokens.map(token => <code key={token} css={tw`block mb-1`}>{token}</code>)}
                            </pre>
                            <div css={tw`text-right`}>
                                <Button css={tw`mt-6`} onClick={dismiss}>
                                    {t('elements:close')}
                                </Button>
                            </div>
                        </>
                        :
                        <Form css={tw`mb-0`}>
                            <FlashMessageRender css={tw`mb-6`} byKey={'account:two-factor'}/>
                            <div css={tw`flex flex-wrap`}>
                                <div css={tw`w-full md:flex-1`}>
                                    <div css={tw`w-32 h-32 md:w-64 md:h-64 bg-neutral-600 p-2 rounded mx-auto`}>
                                        {!token || !token.length ?
                                            <img
                                                src={'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='}
                                                css={tw`w-64 h-64 rounded`}
                                            />
                                            :
                                            <img
                                                src={`https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${token}`}
                                                onLoad={() => setLoading(false)}
                                                css={tw`w-full h-full shadow-none rounded-none`}
                                            />
                                        }
                                    </div>
                                </div>
                                <div css={tw`w-full mt-6 md:mt-0 md:flex-1 md:flex md:flex-col`}>
                                    <div css={tw`flex-1`}>
                                        <Field
                                            id={'code'}
                                            name={'code'}
                                            type={'text'}
                                            title={t('dashboard:2fa.setup.input_title')}
                                            description={t('dashboard:2fa.setup.input_desc')}
                                            autoFocus={!loading}
                                        />
                                    </div>
                                    <div css={tw`mt-6 md:mt-0 text-right`}>
                                        <Button>
                                            {t('elements:setup')}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Form>
                    }
                </Modal>
            )}
        </Formik>
    );
};

export default withTranslation([ 'elements', 'dashboard' ])(SetupTwoFactorModal);
