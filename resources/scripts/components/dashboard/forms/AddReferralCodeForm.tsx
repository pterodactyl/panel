import React from 'react';
import * as Yup from 'yup';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import Field from '@/components/elements/Field';
import { Form, Formik, FormikHelpers } from 'formik';
import { Actions, useStoreActions } from 'easy-peasy';
import { Button } from '@/components/elements/button/index';
import useReferralCode from '@/api/account/useReferralCode';
import { useStoreState } from '@/state/hooks';

interface Values {
    code: string;
    password: string;
}

const schema = Yup.object().shape({
    code: Yup.string().length(16).required(),
    password: Yup.string().required('You must provide your current account password.'),
});

export default () => {
    const { clearFlashes, addFlash } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const alreadyReferred = useStoreState((state) => state.user.data!.referralCode);

    const submit = (values: Values, { resetForm, setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('account:email');

        useReferralCode({ ...values })
            .then(() => {
                // @ts-expect-error this is valid
                window.location = '/account';
            })
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
            {alreadyReferred ? (
                <>
                    <p className={'my-2 text-gray-400'}>
                        You have already used a referral code.
                        {' ('}
                        <span className={'text-gray-200 text-white bg-gray-800 rounded-xl w-fit px-2 text-center'}>
                            {alreadyReferred}
                        </span>
                        {') '}
                    </p>
                </>
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
