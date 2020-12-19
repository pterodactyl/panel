import React from 'react';
import { Form, Formik, FormikHelpers } from 'formik';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import FlashMessageRender from '@/components/FlashMessageRender';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import disableAccountTwoFactor from '@/api/account/disableAccountTwoFactor';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { useTranslation } from 'react-i18next';

interface Values {
    password: string;
}

export default ({ ...props }: RequiredModalProps) => {
    const { clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);
    const { t } = useTranslation('dashboard');

    const submit = ({ password }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        disableAccountTwoFactor(password)
            .then(() => {
                updateUserData({ useTotp: false });
                props.onDismissed();
            })
            .catch(error => {
                console.error(error);

                clearAndAddHttpError({ error, key: 'account:two-factor' });
                setSubmitting(false);
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                password: '',
            }}
            validationSchema={object().shape({
                password: string().required(t('need_current_password')),
            })}
        >
            {({ isSubmitting, isValid }) => (
                <Modal {...props} dismissable={!isSubmitting} showSpinnerOverlay={isSubmitting}>
                    <Form className={'mb-0'}>
                        <FlashMessageRender css={tw`mb-6`} byKey={'account:two-factor'}/>
                        <Field
                            id={'password'}
                            name={'password'}
                            type={'password'}
                            label={t('current_password')}
                            description={t('disable_2fa_text')}
                            autoFocus
                        />
                        <div css={tw`mt-6 text-right`}>
                            <Button
                                color={'red'}
                                disabled={!isValid}
                            >
                                {t('disable_2fa')}
                            </Button>
                        </div>
                    </Form>
                </Modal>
            )}
        </Formik>
    );
};
