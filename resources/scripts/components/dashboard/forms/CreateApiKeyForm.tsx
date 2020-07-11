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
import { ApiKey } from '@/api/account/getApiKeys';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Input, { Textarea } from '@/components/elements/Input';

interface Values {
    description: string;
    allowedIps: string;
}

export default ({ onKeyCreated }: { onKeyCreated: (key: ApiKey) => void }) => {
    const [ apiKey, setApiKey ] = useState('');
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearFlashes('account');
        createApiKey(values.description, values.allowedIps)
            .then(({ secretToken, ...key }) => {
                resetForm();
                setSubmitting(false);
                setApiKey(`${key.identifier}${secretToken}`);
                onKeyCreated(key);
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
                <h3 css={tw`mb-6`}>Your API Key</h3>
                <p css={tw`text-sm mb-6`}>
                    The API key you have requested is shown below. Please store this in a safe location, it will not be
                    shown again.
                </p>
                <pre css={tw`text-sm bg-neutral-900 rounded py-2 px-4 font-mono`}>
                    <code css={tw`font-mono`}>{apiKey}</code>
                </pre>
                <div css={tw`flex justify-end mt-6`}>
                    <Button
                        type={'button'}
                        onClick={() => setApiKey('')}
                    >
                        Close
                    </Button>
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
                            css={tw`mb-6`}
                        >
                            <Field name={'description'} as={Input}/>
                        </FormikFieldWrapper>
                        <FormikFieldWrapper
                            label={'Allowed IPs'}
                            name={'allowedIps'}
                            description={'Leave blank to allow any IP address to use this API key, otherwise provide each IP address on a new line.'}
                        >
                            <Field as={Textarea} name={'allowedIps'} css={tw`h-32`}/>
                        </FormikFieldWrapper>
                        <div css={tw`flex justify-end mt-6`}>
                            <Button>Create</Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </>
    );
};
