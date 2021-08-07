import React, { useState } from 'react';
import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import createApiKey from '@/api/account/createApiKey';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { ApiKey } from '@/api/account/getApiKeys';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import ApiKeyModal from '@/components/dashboard/ApiKeyModal';

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
                setApiKey(secretToken);
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
            <ApiKeyModal
                visible={apiKey.length > 0}
                onModalDismissed={() => setApiKey('')}
                apiKey={apiKey}
            />
            <Formik
                onSubmit={submit}
                initialValues={{ description: '', allowedIps: '' }}
                validationSchema={object().shape({
                    description: string().required().min(4),
                })}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <SpinnerOverlay visible={isSubmitting}/>
                        <FormikFieldWrapper
                            label={'Description'}
                            name={'description'}
                            description={'This API key will be able to act on your behalf against this Panel\'s API.'}
                            css={tw`mb-6`}
                        >
                            <Field name={'description'} as={Input}/>
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
