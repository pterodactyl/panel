import React from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import * as Yup from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Field from '@/components/elements/Field';
import { httpErrorToHuman } from '@/api/http';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { withTranslation, WithTranslation } from 'react-i18next';

interface Values {
    email: string;
    password: string;
}

const UpdateEmailAddressForm = ({ t }: WithTranslation) => {
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const updateEmail = useStoreActions((state: Actions<ApplicationStore>) => state.user.updateUserEmail);

    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const schema = Yup.object().shape({
        email: Yup.string().email(t('account:email.valid')).required(t('account:email.required')),
        password: Yup.string().required(t('account:password.required')),
    });

    const submit = (values: Values, { resetForm, setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:email');

        updateEmail({ ...values })
            .then(() => addFlash({
                type: 'success',
                key: 'account:email',
                message: t('account:email.updated'),
            }))
            .catch(error => addFlash({
                type: 'error',
                key: 'account:email',
                title: t('elements:error'),
                message: httpErrorToHuman(error),
            }))
            .then(() => {
                resetForm();
                setSubmitting(false);
            });
    };

    return (
        <Formik
            onSubmit={submit}
            validationSchema={schema}
            initialValues={{ email: user!.email, password: '' }}
        >
            {
                ({ isSubmitting, isValid }) => (
                    <React.Fragment>
                        <SpinnerOverlay size={'large'} visible={isSubmitting}/>
                        <Form css={tw`m-0`}>
                            <Field
                                id={'current_email'}
                                type={'email'}
                                name={'email'}
                                label={t('elements:email')}
                            />
                            <div css={tw`mt-6`}>
                                <Field
                                    id={'confirm_password'}
                                    type={'password'}
                                    name={'password'}
                                    label={t('account:password.confirm')}
                                />
                            </div>
                            <div css={tw`mt-6`}>
                                <Button size={'small'} disabled={isSubmitting || !isValid}>
                                    {t('account:email.update')}
                                </Button>
                            </div>
                        </Form>
                    </React.Fragment>
                )
            }
        </Formik>
    );
};

export default withTranslation([ 'elements', 'account' ])(UpdateEmailAddressForm);
