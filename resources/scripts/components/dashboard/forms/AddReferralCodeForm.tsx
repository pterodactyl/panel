import React from 'react';
import * as Yup from 'yup';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from '@/state/hooks';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers } from 'formik';
import { Actions, useStoreActions } from 'easy-peasy';
import { Button } from '@/components/elements/button/index';
import useReferralCode from '@/api/account/useReferralCode';

interface Values {
    code: string;
    password: string;
}

const schema = Yup.object().shape({
    code: Yup.string().length(16).required(),
    password: Yup.string().required('You must provide your current account password.'),
});

export default () => {
    const code = useStoreState((state) => state.user.data!.referralCode);
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (values: Values, { resetForm, setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:referral');

        useReferralCode({ ...values })
            .then(() =>
                addFlash({
                    type: 'success',
                    key: 'account:referral',
                    message: 'You are now using a referral code.',
                })
            )
            .catch((error) =>
                addFlash({
                    type: 'error',
                    key: 'account:referral',
                    title: 'Error',
                    message: httpErrorToHuman(error),
                })
            )
            .then(() => {
                resetForm();
                setSubmitting(false);
            });
    };

    return (
        <>
            {code ? (
                <p className={'my-2 text-gray-400'}>
                    You have already used a referral code.
                    <span className={'bg-gray-800 rounded p-1 ml-2'}>{code}</span>
                </p>
            ) : (
                <Formik onSubmit={submit} initialValues={{ code: '', password: '' }} validationSchema={schema}>
                    {({ isSubmitting, isValid }) => (
                        <React.Fragment>
                            <Form className={'m-0'}>
                                <Field id={'code'} type={'text'} name={'code'} label={'Enter referral code'} />
                                <div className={'mt-6'}>
                                    <Field
                                        id={'confirm_password'}
                                        type={'password'}
                                        name={'password'}
                                        label={'Confirm Password'}
                                    />
                                </div>
                                <div className={'mt-6'}>
                                    <Button disabled={isSubmitting || !isValid}>Use Code</Button>
                                </div>
                            </Form>
                        </React.Fragment>
                    )}
                </Formik>
            )}
        </>
    );
};
