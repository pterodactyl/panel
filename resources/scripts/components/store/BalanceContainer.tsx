import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import ContentBox from '@/components/elements/ContentBox';
import { getResources, Resources } from '@/api/store/getResources';
import PageContentBlock from '@/components/elements/PageContentBlock';
import StripePurchaseForm from '@/components/store/forms/StripePurchaseForm';
import PaypalPurchaseForm from '@/components/store/forms/PaypalPurchaseForm';

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
    const paypal = useStoreState((state) => state.storefront.data!.gateways?.paypal);
    const stripe = useStoreState((state) => state.storefront.data!.gateways?.stripe);

    useEffect(() => {
        getResources().then((resources) => setResources(resources));
    }, []);

    if (!resources) return <Spinner size={'large'} centered />;

    return (
        <PageContentBlock title={'Account Balance'}>
            <h1 className={'j-left text-5xl'}>Account Balance</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500'}>Purchase credits easily via Stripe or PayPal.</h3>
            <Container className={'j-up lg:grid lg:grid-cols-2 my-10'}>
                <ContentBox title={'Account Balance'} showFlashes={'account:balance'} css={tw`sm:mt-0`}>
                    <h1 css={tw`text-7xl flex justify-center items-center`}>
                        {resources.balance} <span className={'text-base ml-4'}>credits</span>
                    </h1>
                </ContentBox>
                <ContentBox title={'Purchase credits'} showFlashes={'account:balance'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    {paypal && <PaypalPurchaseForm />}
                    {stripe && <StripePurchaseForm />}
                    <p className={'text-gray-400 text-sm'}>
                        If no gateways appear here, it&apos;s because they haven&apos;t been configured yet.
                    </p>
                </ContentBox>
            </Container>
            {earn.enabled && (
                <>
                    <h1 className={'j-left text-5xl'}>Idle Credit Earning</h1>
                    <h3 className={'j-left text-2xl text-neutral-500'}>
                        See how many credits you will recieve per minute of AFK.
                    </h3>
                    <Container className={'j-up lg:grid lg:grid-cols-2 my-10'}>
                        <ContentBox title={'Earn Rate'} showFlashes={'earn:rate'} css={tw`sm:mt-0`}>
                            <h1 css={tw`text-7xl flex justify-center items-center`}>
                                {earn.amount} <span className={'text-base ml-4'}>credits / min</span>
                            </h1>
                        </ContentBox>
                        <ContentBox title={'How to earn'} showFlashes={'earn:how'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                            <p>You can earn credits by having any page of this panel open.</p>
                            <p css={tw`mt-1`}>
                                <span css={tw`text-green-500`}>{earn.amount}&nbsp;</span>
                                credit(s) per minute will automatically be added to your account, as long as this site
                                is open in a browser tab.
                            </p>
                        </ContentBox>
                    </Container>
                </>
            )}
        </PageContentBlock>
    );
};
