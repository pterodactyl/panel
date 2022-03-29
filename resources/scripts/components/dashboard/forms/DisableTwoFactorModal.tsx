import React, { useContext } from 'react';
import { Form, Formik, FormikHelpers } from 'formik';
import FlashMessageRender from '@/components/FlashMessageRender';
import Field from '@/components/elements/Field';
import { object, string } from 'yup';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import disableAccountTwoFactor from '@/api/account/disableAccountTwoFactor';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';
import asModal from '@/hoc/asModal';
import ModalContext from '@/context/ModalContext';

interface Values {
    password: string;
}

const DisableTwoFactorModal = ({ t }: WithTranslation) => {
    const { dismiss, setPropOverrides } = useContext(ModalContext);
    const { clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const updateUserData = useStoreActions((actions: Actions<ApplicationStore>) => actions.user.updateUserData);

    const submit = ({ password }: Values, { setSubmitting }: FormikHelpers<Values>) => {
        setPropOverrides({ showSpinnerOverlay: true, dismissable: false });
        disableAccountTwoFactor(password)
            .then(() => {
                updateUserData({ useTotp: false });
                dismiss();
            })
            .catch(error => {
                console.error(error);

                clearAndAddHttpError({ error, key: 'account:two-factor' });
                setSubmitting(false);
                setPropOverrides(null);
            });
    };

    return (
        <Formik
            onSubmit={submit}
            initialValues={{
                password: '',
            }}
            validationSchema={object().shape({
                password: string().required(t('account:password.required')),
            })}
        >
            {({ isValid }) => (
                <Form className={'mb-0'}>
                    <FlashMessageRender css={tw`mb-6`} byKey={'account:two-factor'}/>
                    <Field
                        id={'password'}
                        name={'password'}
                        type={'password'}
                        label={t('account:password.current')}
                        description={t('account:2fa.disable_password_desc')}
                        autoFocus
                    />
                    <div css={tw`mt-6 text-right`}>
                        <Button color={'red'} disabled={!isValid}>
                            {t('elements:disable')}
                        </Button>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal()(withTranslation([ 'elements', 'account' ])(DisableTwoFactorModal));
