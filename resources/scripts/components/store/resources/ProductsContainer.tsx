import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import Button from '@/components/elements/Button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import PageContentBlock from '@/components/elements/PageContentBlock';
import purchaseResource from '@/api/store/purchaseResource';

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

export default () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();

    const purchase = (resource: string) => {
        clearFlashes('store:resources');

        purchaseResource(resource)
            .then(() => addFlash({
                type: 'success',
                key: 'store:resources',
                message: 'Resource has been added to your account.',
            }))
            .catch(error => {
                clearAndAddHttpError({ key: 'store:resources', error });
            });
    };

    return (
        <PageContentBlock title={'Store Products'} showFlashKey={'store:resources'}>
            <h1 css={tw`text-5xl`}>Order resources</h1>
            <h3 css={tw`text-2xl ml-4 text-neutral-500`}>Buy more resources to add to your server.</h3>
            <Container css={tw`lg:grid lg:grid-cols-3 my-10`}>
                <TitledGreyBox title={'Purchase CPU'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Cpu css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('cpu')}
                        >
                            +50% CPU
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('memory')}
                        >
                            +1GB RAM
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('disk')}
                        >
                            +1GB DISK
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
            </Container>
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
                <TitledGreyBox title={'Purchase Server Slot'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Server css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('slots')}
                        >
                            +1 slot
                        </Button>
                    </Wrapper>

                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Ports'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Share2 css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('ports')}
                        >
                            +1 port
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Backups'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Archive css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('backups')}
                        >
                            +1 backup
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Databases'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Database css={tw`ml-4`} size={40} />
                        <Button
                            isSecondary
                            onClick={() => purchase('databases')}
                        >
                            +1 database
                        </Button>
                    </Wrapper>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};
