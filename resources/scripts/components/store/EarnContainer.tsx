import tw from 'twin.macro';
import React, { useEffect, useState } from 'react';
import StoreError from '@/components/store/error/StoreError';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';
import { useStoreState } from '@/state/hooks';

const BalanceContainer = () => {
    const [ resources, setResources ] = useState<Resources>();
    const earn = useStoreState(state => state.settings.data!.earn);

    useEffect(() => {
        getResources()
            .then(resources => setResources(resources));
    }, []);

    if (!resources) return <StoreError />;

    return (
        <PageContentBlock title={'Account Balance'}>
            <h1 css={tw`text-5xl`}>Earn credits</h1>
            <h3 css={tw`text-2xl mt-2 text-neutral-500`}>You currently have {resources.balance} credits.</h3>
            <h5 css={tw`text-lg mt-4 text-neutral-500 flex justify-center items-center`}>You will earn {earn.amount} coins per minute by having any page of this site open.</h5>
        </PageContentBlock>
    );
};

export default BalanceContainer;
