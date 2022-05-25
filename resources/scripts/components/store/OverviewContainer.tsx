import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import { useStoreState } from 'easy-peasy';
import styled from 'styled-components/macro';
import { megabytesToHuman } from '@/helpers';
import TitledGreyBox from '../elements/TitledGreyBox';
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

const OverviewContainer = () => {
    const user = useStoreState(state => state.user.data!);

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <Container css={tw`lg:grid lg:grid-cols-5 my-10`}>
                <TitledGreyBox title={'Total Slots Available'}>
                    Unavailable
                </TitledGreyBox>
                <TitledGreyBox title={'Total CPU Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    {user.storeCpu}%
                </TitledGreyBox>
                <TitledGreyBox title={'Total RAM Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    {megabytesToHuman(user.storeMemory)}
                </TitledGreyBox>
                <TitledGreyBox title={'Total Disk Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    {megabytesToHuman(user.storeDisk)}
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};

export default OverviewContainer;
