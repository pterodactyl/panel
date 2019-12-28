import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikActions } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import getTwoFactorTokenUrl from '@/api/account/getTwoFactorTokenUrl';
import enableAccountTwoFactor from '@/api/account/enableAccountTwoFactor';
import FlashMessageRender from '@/components/FlashMessageRender';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';

interface Values {
    code: string;
}

export default ({ ...props }: RequiredModalProps) => {
    const [ token, setToken ] = useState('');
    const [ loading, setLoading ] = useState(true);

    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('account:two-factor');
        getTwoFactorTokenUrl()
            .then(setToken)
            .catch(error => {
                console.error(error);
            });
    }, []);

    const submit = ({ code }: Values, { setSubmitting }: FormikActions<Values>) => {
        clearFlashes('account:two-factor');
        enableAccountTwoFactor(code)
            .then(() => {
                updateUserData({ useTotp: true });
                props.onDismissed();
            })
            .catch(error => {
                console.error(error);

                addError({ message: httpErrorToHuman(error), key: 'account:two-factor' });
                setSubmitting(false);
            });
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
                    dismissable={!isSubmitting}
                    showSpinnerOverlay={loading || isSubmitting}
                >
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
                </Modal>
            )}
        </Formik>
    );
};
