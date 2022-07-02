import tw from 'twin.macro';
import asModal from '@/hoc/asModal';
import { object, string } from 'yup';
import React, { useContext } from 'react';
import { ApplicationStore } from '@/state';
import Field from '@/components/elements/Field';
import ModalContext from '@/context/ModalContext';
import { Form, Formik, FormikHelpers } from 'formik';
import { Actions, useStoreActions } from 'easy-peasy';
import { Button } from '@/components/elements/button/index';
import FlashMessageRender from '@/components/FlashMessageRender';
import disableAccountTwoFactor from '@/api/account/disableAccountTwoFactor';

interface Values {
    password: string;
}

const DisableTwoFactorModal = () => {
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
            .catch((error) => {
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
                password: string().required('You must provide your current password in order to continue.'),
            })}
        >
            {({ isValid }) => (
                <Form className={'mb-0'}>
                    <FlashMessageRender css={tw`mb-6`} byKey={'account:two-factor'} />
                    <Field
                        id={'password'}
                        name={'password'}
                        type={'password'}
                        label={'Current Password'}
                        description={
                            'In order to disable two-factor authentication you will need to provide your account password.'
                        }
                        autoFocus
                    />
                    <div css={tw`mt-6 text-right`}>
                        <Button.Danger disabled={!isValid}>
                            Disable 2FA
                        </Button.Danger>
                    </div>
                </Form>
            )}
        </Formik>
    );
};

export default asModal()(DisableTwoFactorModal);
