import React, { useState } from 'react';
import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import Modal from '@/components/elements/Modal';
import createApiKey from '@/api/account/createApiKey';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

interface Values {
    description: string;
    allowedIps: string;
}

export default () => {
    const [ apiKey, setApiKey ] = useState('');
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes('account');
        createApiKey(values.description, values.allowedIps)
            .then(key => {
                resetForm();
                setSubmitting(false);
                setApiKey(`${key.identifier}.${key.secretToken}`);
            })
            .catch(error => {
                console.error(error);

                addError({ key: 'account', message: httpErrorToHuman(error) });
                setSubmitting(false);
            });
    };

    return (
        <>
            <Modal
                visible={apiKey.length > 0}
                onDismissed={() => setApiKey('')}
                closeOnEscape={false}
                closeOnBackground={false}
            >
                <h3 className={'mb-6'}>Your API Key</h3>
                <p className={'text-sm mb-6'}>
                    The API key you have requested is shown below. Please store this in a safe location, it will not be
                    shown again.
                </p>
                <pre className={'text-sm bg-neutral-900 rounded py-2 px-4 font-mono'}>
                    <code className={'font-mono'}>{apiKey}</code>
                </pre>
                <div className={'flex justify-end mt-6'}>
                    <button
                        type={'button'}
                        className={'btn btn-secondary btn-sm'}
                        onClick={() => setApiKey('')}
                    >
                        Close
                    </button>
                </div>
            </Modal>
            <Formik
                onSubmit={submit}
                initialValues={{
                    description: '',
                    allowedIps: '',
                }}
                validationSchema={object().shape({
                    allowedIps: string(),
                    description: string().required().min(4),
                })}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <SpinnerOverlay visible={isSubmitting}/>
                        <FormikFieldWrapper
                            label={'Description'}
                            name={'description'}
                            description={'A description of this API key.'}
                            className={'mb-6'}
                        >
                            <Field name={'description'} className={'input-dark'}/>
                        </FormikFieldWrapper>
                        <FormikFieldWrapper
                            label={'Allowed IPs'}
                            name={'allowedIps'}
                            description={'Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'}
                        >
                            <Field
                                as={'textarea'}
                                name={'allowedIps'}
                                className={'input-dark h-32'}
                            />
                        </FormikFieldWrapper>
                        <div className={'flex justify-end mt-6'}>
                            <button className={'btn btn-primary btn-sm'}>
                                Create
                            </button>
                        </div>
                    </Form>
                )}
            </Formik>
        </>
    );
};
