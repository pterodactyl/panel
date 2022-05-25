import React from 'react';
import tw from 'twin.macro';
import { useStoreState } from 'easy-peasy';

const BalanceContainer = () => {
    const user = useStoreState(state => state.user.data);

    return (
        <p css={tw`text-green-500`}>You have {user?.storeBalance} credits available.</p>
    );
};

export default BalanceContainer;
