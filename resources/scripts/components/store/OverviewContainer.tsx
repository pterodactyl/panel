import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
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

const Wrapper = styled.div`
  ${tw`text-2xl flex flex-row justify-center items-center`};
`;

const OverviewContainer = () => {
    const user = useStoreState(state => state.user.data!);

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <h1 css={tw`text-5xl`}>👋 Hey, {user.username}!</h1>
            <h3 css={tw`text-2xl mt-2 text-neutral-500`}>Welcome to the Jexactyl storefront.</h3>
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
                <TitledGreyBox title={'Total Slots'}>
                    <Wrapper>
                        <Icon.Server css={tw`mr-2`} /> N/A
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total CPU'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Cpu css={tw`mr-2`} /> {user.storeCpu}%
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart css={tw`mr-2`} /> {megabytesToHuman(user.storeMemory)}
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive css={tw`mr-2`} /> {megabytesToHuman(user.storeDisk)}
                    </Wrapper>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};

export default OverviewContainer;
