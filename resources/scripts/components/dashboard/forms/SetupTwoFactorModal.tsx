import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import getTwoFactorTokenUrl from '@/api/account/getTwoFactorTokenUrl';
import enableAccountTwoFactor from '@/api/account/enableAccountTwoFactor';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import Field from '@/components/elements/Field';

interface Values {
    code: string;
}

export default ({ onDismissed, ...props }: RequiredModalProps) => {
    const [ token, setToken ] = useState('');
    const [ loading, setLoading ] = useState(true);
    const [ recoveryTokens, setRecoveryTokens ] = useState<string[]>([]);

    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('account:two-factor');
        getTwoFactorTokenUrl()
            .then(setToken)
            .catch(error => {
                console.error(error);
                addError({ message: httpErrorToHuman(error), key: 'account:two-factor' });
            });
    }, []);

    const submit = ({ code }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:two-factor');
        enableAccountTwoFactor(code)
            .then(tokens => {
                setRecoveryTokens(tokens);
            })
            .catch(error => {
                console.error(error);

                addError({ message: httpErrorToHuman(error), key: 'account:two-factor' });
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
                    .required('You must provide an authentication code to continue.')
                    .matches(/^(\d){6}$/, 'Authenticator code must be 6 digits.'),
            })}
        >
            {({ isSubmitting, isValid }) => (
                <Modal
                    {...props}
                    onDismissed={dismiss}
                    dismissable={!isSubmitting}
                    showSpinnerOverlay={loading || isSubmitting}
                    closeOnEscape={!recoveryTokens}
                    closeOnBackground={!recoveryTokens}
                >
                    {recoveryTokens.length > 0 ?
                        <>
                            <h2 className={'mb-4'}>Two-factor authentication enabled</h2>
                            <p className={'text-neutral-300'}>
                                Two-factor authentication has been enabled on your account. Should you loose access to
                                this device you'll need to use on of the codes displayed below in order to access your
                                account.
                            </p>
                            <p className={'text-neutral-300 mt-4'}>
                                <strong>These codes will not be displayed again.</strong> Please take note of them now
                                by storing them in a secure repository such as a password manager.
                            </p>
                            <pre className={'mt-4 rounded font-mono bg-neutral-900 p-4'}>
                                {recoveryTokens.map(token => <code key={token} className={'block mb-1'}>{token}</code>)}
                            </pre>
                            <div className={'text-right'}>
                                <button className={'mt-6 btn btn-lg btn-primary'} onClick={dismiss}>
                                    Close
                                </button>
                            </div>
                        </>
                        :
                        <Form className={'mb-0'}>
                            <FlashMessageRender className={'mb-6'} byKey={'account:two-factor'}/>
                            <div className={'flex flex-wrap'}>
                                <div className={'w-full md:flex-1'}>
                                    <div className={'w-32 h-32 md:w-64 md:h-64 bg-neutral-600 p-2 rounded mx-auto'}>
                                        {!token || !token.length ?
                                            <img
                                                src={'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='}
                                                className={'w-64 h-64 rounded'}
                                            />
                                            :
                                            <img
                                                src={`https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${token}`}
                                                onLoad={() => setLoading(false)}
                                                className={'w-full h-full shadow-none rounded-0'}
                                            />
                                        }
                                    </div>
                                </div>
                                <div className={'w-full mt-6 md:mt-0 md:flex-1 md:flex md:flex-col'}>
                                    <div className={'flex-1'}>
                                        <Field
                                            id={'code'}
                                            name={'code'}
                                            type={'text'}
                                            title={'Code From Authenticator'}
                                            description={'Enter the code from your authenticator device after scanning the QR image.'}
                                            autoFocus={!loading}
                                        />
                                    </div>
                                    <div className={'mt-6 md:mt-0 text-right'}>
                                        <button className={'btn btn-primary btn-sm'} disabled={!isValid}>
                                            Setup
                                        </button>
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
