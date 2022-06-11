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
import Input, { Textarea } from '@/components/elements/Input';
import styled from 'styled-components/macro';
import ApiKeyModal from '@/components/dashboard/ApiKeyModal';

interface Values {
    description: string;
    allowedIps: string;
}

const CustomTextarea = styled(Textarea)`${tw`h-32`}`;

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
            <ApiKeyModal
                visible={apiKey.length > 0}
                onModalDismissed={() => setApiKey('')}
                apiKey={apiKey}
            />
            <Formik
                onSubmit={submit}
                initialValues={{ description: '', allowedIps: '' }}
                validationSchema={object().shape({
                    allowedIps: string(),
                    description: string().required().min(4),
                })}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <SpinnerOverlay visible={isSubmitting} />
                        <FormikFieldWrapper
                            label={'描述'}
                            name={'description'}
                            description={'此 API 密钥的描述。'}
                            css={tw`mb-6`}
                        >
                            <Field name={'description'} as={Input} />
                        </FormikFieldWrapper>
                        <FormikFieldWrapper
                            label={'IP 白名单'}
                            name={'allowedIps'}
                            description={'留空以允许任何 IP 地址使用此 API 密钥，否则在每一行中提供每个 IP 地址。'}
                        >
                            <Field name={'allowedIps'} as={CustomTextarea} />
                        </FormikFieldWrapper>
                        <div css={tw`flex justify-end mt-6`}>
                            <Button>创建</Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </>
    );
};
