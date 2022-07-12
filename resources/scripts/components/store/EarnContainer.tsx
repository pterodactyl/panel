import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import React, { useEffect, useState } from 'react';
import ContentBox from '@/components/elements/ContentBox';
import StoreError from '@/components/store/error/StoreError';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';

const Container = styled.div`
    ${tw`flex flex-wrap`};

    & > div {
        ${tw`w-full`};

        ${breakpoint('sm')`
      width: calc(50% - 1rem);
    `}

        ${breakpoint('md')`
      ${tw`w-auto flex-1`};
    `}
    }
`;

export default () => {
    const [resources, setResources] = useState<Resources>();
    const earn = useStoreState((state) => state.storefront.data!.earn);
    const store = useStoreState((state) => state.storefront.data!);

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <StoreError />;

    return (
        <PageContentBlock title={'Account Balance'}>
            <h1 className={'j-left text-5xl'}>Earn credits</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>Passively earn credits by using the panel.</h3>
            <Container className={'j-up lg:grid lg:grid-cols-3 my-10'}>
                <ContentBox title={'Current Account Balance'} showFlashes={'earn:balance'} css={tw`sm:mt-0`}>
                    <h1 css={tw`text-7xl flex justify-center items-center`}>
                        ${resources.balance} {store.currency}
                    </h1>
                </ContentBox>
                <ContentBox title={'Earn Rate'} showFlashes={'earn:rate'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <h1 css={tw`text-7xl flex justify-center items-center`}>
                        {earn.amount} {store.currency} / min
                    </h1>
                </ContentBox>
                <ContentBox title={'How to earn'} showFlashes={'earn:how'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <p>You can earn credits by having any page of this panel open.</p>
                    <p css={tw`mt-1`}>
                        <span css={tw`text-green-500`}>{earn.amount}&nbsp;</span>
                        credit(s) per minute will automatically be added to your account, as long as this site is open
                        in a browser tab.
                    </p>
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};
