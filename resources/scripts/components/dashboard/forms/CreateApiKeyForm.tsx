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
import { useTranslation } from 'react-i18next';

interface Values {
    description: string;
    allowedIps: string;
}

const CustomTextarea = styled(Textarea)`${tw`h-32`}`;

export default ({ onKeyCreated }: { onKeyCreated: (key: ApiKey) => void }) => {
    const [ apiKey, setApiKey ] = useState('');
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const { t } = useTranslation([ 'account', 'elements' ]);

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
                    description: string().required(t('api.required', { ns: 'account' })).min(4, t('api.valid', { ns: 'account' })),
                })}
            >
                {({ isSubmitting }) => (
                    <Form>
                        <SpinnerOverlay visible={isSubmitting}/>
                        <FormikFieldWrapper
                            label={t('description', { ns: 'elements' })}
                            name={'description'}
                            description={t('api.key_desc')}
                            css={tw`mb-6`}
                        >
                            <Field name={'description'} as={Input}/>
                        </FormikFieldWrapper>
                        <FormikFieldWrapper
                            label={t('api.allowed_ips_title')}
                            name={'allowedIps'}
                            description={t('api.allowed_ips_desc')}
                        >
                            <Field name={'allowedIps'} as={CustomTextarea}/>
                        </FormikFieldWrapper>
                        <div css={tw`flex justify-end mt-6`}>
                            <Button>{t('create', { ns: 'elements' })}</Button>
                        </div>
                    </Form>
                )}
            </Formik>
        </>
    );
};
