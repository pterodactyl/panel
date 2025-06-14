import React, { useState } from 'react';
import tw from 'twin.macro';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import useFlash from '@/plugins/useFlash';
import { Form, Formik, FormikHelpers } from 'formik';
import startTransaction from '@/api/shop/payment/startTransaction';
import { number, object } from 'yup';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Button from '@/components/elements/Button';
import Field from '@/components/elements/Field';
import { loadStripe } from '@stripe/stripe-js';
import { PaymentEnabledInterface } from '@/components/shop/PaymentContainer';

interface Props {
    stripeKey: string;
    enabled: PaymentEnabledInterface;
    minAmount: number;
    maxAmount: number;
    currency: string;
}

interface CheckoutValues {
    amount: number;
}

export default ({ stripeKey, enabled, minAmount, maxAmount, currency }: Props) => {
    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();

    const [ isSubmit, setSubmit ] = useState(false);

    const stripePromise = loadStripe(stripeKey);

    const submit = ({ amount }: CheckoutValues, { setSubmitting }: FormikHelpers<CheckoutValues>) => {
        clearFlashes('payments');
        setSubmitting(false);
        setSubmit(true);

        startTransaction('stripe', amount).then((data) => {
            setSubmit(false);
            clearFlashes('payments');

            stripePromise.then(stripe => {
                stripe?.redirectToCheckout({
                    sessionId: data.data.sessionId,
                }).then(result => {
                    if (result.error) {
                        addFlash({ key: 'payments', message: 'Failed to make the transaction. Please try again later...', type: 'error', title: 'Error' });
                    }
                });
            });
        }).catch((error) => {
            setSubmit(false);
            clearAndAddHttpError({ key: 'payments', error });
        });
    };

    return (
        <div css={enabled.paypal === 0 ? tw`w-full pt-4` : tw`w-full md:w-6/12 md:pr-2 pt-4`}>
            <TitledGreyBox title={'Checkout with Stripe'}>
                <Formik
                    onSubmit={submit}
                    initialValues={{ amount: 0 }}
                    validationSchema={object().shape({
                        amount: number().required().min(minAmount).max(maxAmount),
                    })}
                >
                    <React.Fragment>
                        <SpinnerOverlay visible={isSubmit} size={'large'} />
                        <Form>
                            <div css={tw`flex flex-wrap`}>
                                <p css={tw`text-sm pb-4`}>
                                    When you start the payment, you will be redirected to official <a href={'https://stripe.com/'} target={'_blank'} rel={'noreferrer'}>Stripe</a> Checkout page.
                                </p>
                                <div css={tw`mb-6 w-full pt-2`}>
                                    <Field
                                        name={'amount'}
                                        label={`Amount (${currency})`}
                                        type={'number'}
                                    />
                                </div>
                            </div>
                            <div css={tw`flex justify-end`}>
                                <Button type={'submit'} disabled={isSubmit}>Start Payment</Button>
                            </div>
                        </Form>
                    </React.Fragment>
                </Formik>
            </TitledGreyBox>
        </div>
    );
};
