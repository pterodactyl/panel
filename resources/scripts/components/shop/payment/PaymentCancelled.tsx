import React from 'react';
import NotFoundSvg from '@/assets/images/not_found.svg';
import ScreenBlock from '@/components/elements/ScreenBlock';

export default () => {
    return (
        <ScreenBlock
            title={'Payment Cancelled'}
            image={NotFoundSvg}
            message={'The payment was cancelled. If you want to make a payment again, go payments and start a transaction.'}
        />
    );
};
