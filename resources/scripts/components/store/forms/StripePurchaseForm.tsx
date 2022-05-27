import tw from 'twin.macro';
import React, { useState } from 'react';
import Button from '@/components/elements/Button';
import Select from '@/components/elements/Select';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';

export default () => {
    const [ submitting, setSubmitting ] = useState(false);

    const submit = () => {
        setSubmitting(true);

        console.log('Works');

        setSubmitting(false);
    };

    return (
        <TitledGreyBox title={'Purchase via Stripe'} css={tw`mt-8 sm:mt-0`}>
            <SpinnerOverlay size={'large'} visible={submitting} />
            <Select
                name={'amount'}
                disabled={submitting}
            >
                <option key={'credits:placeholder'}>Choose an amount...</option>
                <option key={'credits:buy:100'} value={100}>Purchase 100 credits</option>
            </Select>
            <div css={tw`mt-6`}>
                <Button size={'small'} onSubmit={submit} disabled={submitting}>
                    Purchase via Stripe
                </Button>
            </div>
        </TitledGreyBox>
    );
};
