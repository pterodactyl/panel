import React from 'react';
import NotFoundSvg from '@/assets/images/not_found.svg';
import ScreenBlock from '@/components/elements/ScreenBlock';

export default () => {
    return (
        <ScreenBlock
            title={'Payment Cancelled'}
            image={NotFoundSvg}
            message={'Payment cancelled. To complete your purchase, go to Payments and begin a new transaction.'}
        />
    );
};
