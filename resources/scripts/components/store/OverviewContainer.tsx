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

const OverviewContainer = () => {
    const user = useStoreState(state => state.user.data!);

    return (
        <PageContentBlock title={'Storefront Overview'}>
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
                <TitledGreyBox title={'Total Slots Available'}>
                    <div css={tw`flex flex-row`}>
                        <Icon.Server css={tw`mr-1`} size={20} /> Unavailable
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Total CPU Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <div css={tw`flex flex-row`}>
                        <Icon.Cpu css={tw`mr-1`} size={20} /> {user.storeCpu}%
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Total RAM Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <div css={tw`flex flex-row`}>
                        <Icon.PieChart css={tw`mr-1`} size={20} /> {megabytesToHuman(user.storeMemory)}
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Total Disk Available'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <div css={tw`flex flex-row`}>
                        <Icon.HardDrive css={tw`mr-1`} size={20} /> {megabytesToHuman(user.storeDisk)}
                    </div>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};

export default OverviewContainer;
