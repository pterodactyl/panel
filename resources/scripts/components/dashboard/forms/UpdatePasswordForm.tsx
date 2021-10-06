import React from 'react';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { Form, Formik, FormikHelpers } from 'formik';
import Field from '@/components/elements/Field';
import * as Yup from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import updateAccountPassword from '@/api/account/updateAccountPassword';
import { httpErrorToHuman } from '@/api/http';
import { ApplicationStore } from '@/state';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import { useTranslation } from 'react-i18next';

interface Values {
    current: string;
    password: string;
    confirmPassword: string;
}

export default () => {
    const { t } = useTranslation();
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    if (!user) {
        return null;
    }

    const schema = Yup.object().shape({
        current: Yup.string().min(1).required(t('Provide Current Password Pass')),
        password: Yup.string().min(8).required(t('Provide New Password')),
        confirmPassword: Yup.string().test('password', t('Password Not Match Pass'), function (value) {
            return value === this.parent.password;
        }),
    });

    const submit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:password');
        updateAccountPassword({ ...values })
            .then(() => {
                // @ts-ignore
                window.location = '/auth/login';
            })
            .catch(error => addFlash({
                key: 'account:password',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            }))
            .then(() => setSubmitting(false));
    };

    return (
        <React.Fragment>
            <Formik
                onSubmit={submit}
                validationSchema={schema}
                initialValues={{ current: '', password: '', confirmPassword: '' }}
            >
                {
                    ({ isSubmitting, isValid }) => (
                        <React.Fragment>
                            <SpinnerOverlay size={'large'} visible={isSubmitting}/>
                            <Form css={tw`m-0`}>
                                <Field
                                    id={'current_password'}
                                    type={'password'}
                                    name={'current'}
                                    label={t('Current Password')}
                                />
                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'new_password'}
                                        type={'password'}
                                        name={'password'}
                                        label={t('New Password')}
                                        description={t('Exclusive Password')}
                                    />
                                </div>
                                <div css={tw`mt-6`}>
                                    <Field
                                        id={'confirm_new_password'}
                                        type={'password'}
                                        name={'confirmPassword'}
                                        label={t('Confirm New Password')}
                                    />
                                </div>
                                <div css={tw`mt-6`}>
                                    <Button size={'small'} disabled={isSubmitting || !isValid}>
                                        {t('Update Password')}
                                    </Button>
                                </div>
                            </Form>
                        </React.Fragment>
                    )
                }
            </Formik>
        </React.Fragment>
    );
};
