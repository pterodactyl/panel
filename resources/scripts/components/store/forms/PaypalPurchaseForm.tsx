import tw from 'twin.macro';
import { Form, Formik } from 'formik';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import paypal from '@/api/store/gateways/paypal';
import Button from '@/components/elements/Button';
import Select from '@/components/elements/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';

export default () => {
    const { clearAndAddHttpError } = useFlash();
    const [ amount, setAmount ] = useState(0);
    const [ submitting, setSubmitting ] = useState(false);

    const submit = () => {
        setSubmitting(true);

        paypal(amount).then(url => {
            setSubmitting(false);

            // @ts-ignore
            window.location.href = url;
        }).catch(error => {
            console.error(error);
            clearAndAddHttpError({ key: 'store:paypal', error });
            setSubmitting(false);
        });
    };

    return (
        <TitledGreyBox title={'Purchase via PayPal'}>
            <FlashMessageRender byKey={'store:paypal'} css={tw`mb-2`} />
            <Formik
                onSubmit={submit}
                initialValues={{
                    amount: 100,
                }}
            >
                <Form>
                    <SpinnerOverlay size={'large'} visible={submitting} />
                    <Select
                        name={'amount'}
                        disabled={submitting}
                        // @ts-ignore
                        onChange={e => setAmount(e.target.value)}
                    >
                        <option key={'credits:placeholder'} hidden>Choose an amount...</option>
                        <option key={'credits:buy:100'} value={100}>Purchase 100 credits</option>
                        <option key={'credits:buy:200'} value={200}>Purchase 200 credits</option>
                        <option key={'credits:buy:500'} value={500}>Purchase 500 credits</option>
                        <option key={'credits:buy:1000'} value={1000}>Purchase 1000 credits</option>
                    </Select>
                    <div css={tw`mt-6`}>
                        <Button size={'small'} type={'submit'} disabled={submitting}>
                                Purchase via PayPal
                        </Button>
                    </div>
                </Form>
            </Formik>
        </TitledGreyBox>
    );
};
