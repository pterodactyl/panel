import React, { useContext, useEffect, useState } from 'react';
import { Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import getTwoFactorTokenData, { TwoFactorTokenData } from '@/api/account/getTwoFactorTokenData';
import enableAccountTwoFactor from '@/api/account/enableAccountTwoFactor';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import FlashMessageRender from '@/components/FlashMessageRender';
import Field from '@/components/elements/Field';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';
import QRCode from 'qrcode.react';
import CopyOnClick from '@/components/elements/CopyOnClick';

interface Values {
    code: string;
}

const SetupTwoFactorModal = () => {
    const [ token, setToken ] = useState<TwoFactorTokenData | null>(null);
    const [ recoveryTokens, setRecoveryTokens ] = useState<string[]>([]);

    const { dismiss, setPropOverrides } = useContext(ModalContext);
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        getTwoFactorTokenData()
            .then(setToken)
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ error, key: 'account:two-factor' });
            });
    }, []);

    const submit = ({ code }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setPropOverrides(state => ({ ...state, showSpinnerOverlay: true }));
        enableAccountTwoFactor(code)
            .then(tokens => {
                setRecoveryTokens(tokens);
            })
            .catch(error => {
                console.error(error);

                clearAndAddHttpError({ error, key: 'account:two-factor' });
            })
            .then(() => {
                setSubmitting(false);
                setPropOverrides(state => ({ ...state, showSpinnerOverlay: false }));
            });
    };

    useEffect(() => {
        setPropOverrides(state => ({
            ...state,
            closeOnEscape: !recoveryTokens.length,
            closeOnBackground: !recoveryTokens.length,
        }));

        return () => {
            if (recoveryTokens.length > 0) {
                updateUserData({ useTotp: true });
            }
        };
    }, [ recoveryTokens ]);

    return (
        <Formik
            onSubmit={submit}
            initialValues={{ code: '' }}
            validationSchema={object().shape({
                code: string()
                    .required('You must provide an authentication code to continue.')
                    .matches(/^(\d){6}$/, 'Authenticator code must be 6 digits.'),
            })}
        >
            {recoveryTokens.length > 0 ?
                <>
                    <h2 css={tw`text-2xl mb-4`}>Two-factor authentication enabled</h2>
                    <p css={tw`text-neutral-300`}>
                        Two-factor authentication has been enabled on your account. Should you lose access to
                        your authenticator device, you&apos;ll need to use one of the codes displayed below in order to access your
                        account.
                    </p>
                    <p css={tw`text-neutral-300 mt-4`}>
                        <strong>These codes will not be displayed again.</strong> Please take note of them now
                        by storing them in a secure repository such as a password manager.
                    </p>
                    <pre css={tw`text-sm mt-4 rounded font-mono bg-neutral-900 p-4`}>
                        {recoveryTokens.map(token => <code key={token} css={tw`block mb-1`}>{token}</code>)}
                    </pre>
                    <div css={tw`text-right`}>
                        <Button css={tw`mt-6`} onClick={dismiss}>
                            Close
                        </Button>
                    </div>
                </>
                :
                <Form css={tw`mb-0`}>
                    <FlashMessageRender css={tw`mb-6`} byKey={'account:two-factor'}/>
                    <div css={tw`flex flex-wrap`}>
                        <div css={tw`w-full md:flex-1`}>
                            <div css={tw`w-32 h-32 md:w-64 md:h-64 bg-neutral-600 p-2 rounded mx-auto`}>
                                {!token ?
                                    <img
                                        src={'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='}
                                        css={tw`w-64 h-64 rounded`}
                                    />
                                    :
                                    <QRCode
                                        renderAs={'svg'}
                                        value={token.image_url_data}
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
                                    title={'Code From Authenticator'}
                                    description={'Enter the code from your authenticator device after scanning the QR image.'}
                                />
                                {token &&
                                <div css={tw`mt-4 pt-4 border-t border-neutral-500 text-neutral-200`}>
                                    Alternatively, enter the following token into your authenticator application:
                                    <CopyOnClick text={token.secret}>
                                        <div css={tw`text-sm bg-neutral-900 rounded mt-2 py-2 px-4 font-mono`}>
                                            <code css={tw`font-mono`}>
                                                {token.secret}
                                            </code>
                                        </div>
                                    </CopyOnClick>
                                </div>
                                }
                            </div>
                            <div css={tw`mt-6 md:mt-0 text-right`}>
                                <Button>Setup</Button>
                            </div>
                        </div>
                    </div>
                </Form>
            }
        </Formik>
    );
};

export default asModal()(SetupTwoFactorModal);
