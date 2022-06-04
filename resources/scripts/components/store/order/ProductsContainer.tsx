import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import styled from 'styled-components/macro';
import Button from '@/components/elements/Button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
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
  ${tw`flex flex-row justify-center items-center`};
`;

const ProductsContainer = () => {
    return (
        <PageContentBlock title={'Store Products'}>
            <h1 css={tw`text-5xl`}>Order resources</h1>
            <h3 css={tw`text-2xl mt-2 text-neutral-500`}>Buy more resources to add to your server.</h3>
            <Container css={tw`lg:grid lg:grid-cols-3 my-10`}>
                <TitledGreyBox title={'Purchase CPU'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Cpu css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+50% CPU</Button>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1GB RAM</Button>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1GB DISK</Button>
                    </div>
                </TitledGreyBox>
            </Container>
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
                <TitledGreyBox title={'Purchase Server Slot'}>
                    <Wrapper>
                        <Icon.Server css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1 Slot</Button>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Ports'}>
                    <Wrapper>
                        <Icon.Share2 css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1 Port</Button>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Backups'}>
                    <Wrapper>
                        <Icon.Archive css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1 Backup</Button>
                    </div>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Databases'}>
                    <Wrapper>
                        <Icon.Database css={tw`mb-2`} size={40} />
                    </Wrapper>
                    <div css={tw`flex justify-center items-center`}>
                        <Button>+1 Database</Button>
                    </div>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};

export default ProductsContainer;
