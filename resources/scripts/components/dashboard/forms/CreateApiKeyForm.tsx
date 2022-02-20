import React, { useState } from 'react';
import { Field, Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import FormikFieldWrapper from '@/components/elements/FormikFieldWrapper';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import Input from '@/components/elements/Input';
import ApiKeyModal from '@/components/dashboard/ApiKeyModal';
import { createAPIKey, useAPIKeys } from '@/api/account/api-keys';
import { useFlashKey } from '@/plugins/useFlash';

interface Values {
    description: string;
    allowedIps: string;
}

export default () => {
    const [ apiKey, setApiKey ] = useState('');
    const { mutate } = useAPIKeys();
    const { clearAndAddHttpError } = useFlashKey('account');

    const submit = (values: Values, { setSubmitting, resetForm }: FormikHelpers<Values>) => {
        clearAndAddHttpError();

        createAPIKey(values.description)
            .then(async ([ token, secretToken ]) => {
                await mutate((data) => {
                    return (data || []).filter((value) => value.identifier !== token.identifier).concat(token);
                }, false);

                return secretToken;
            })
            .then((token) => {
                resetForm();
                setApiKey(token);
            })
            .catch(error => clearAndAddHttpError(error))
            .finally(() => setSubmitting(false));
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
