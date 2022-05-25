import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import TitledGreyBox from '../elements/TitledGreyBox';
import ContentBox from '@/components/elements/ContentBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import UpdateUsernameForm from '@/components/dashboard/forms/UpdateUsernameForm';

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

const BalanceContainer = () => {
    const user = useStoreState(state => state.user.data!);

    return (
        <PageContentBlock title={'Storefront Balance'}>
            <Container css={tw`lg:grid lg:grid-cols-2 my-10`}>
                <TitledGreyBox title={'Overall balance'}>
                    ${user.storeBalance} available
                </TitledGreyBox>
                <ContentBox
                    title={'Purchase credits'}
                    showFlashes={'account:balance'}
                    css={tw`mt-8 sm:mt-0 sm:ml-8`}
                >
                    <UpdateUsernameForm />
                </ContentBox>
            </Container>
        </PageContentBlock>
    );
};

export default BalanceContainer;
