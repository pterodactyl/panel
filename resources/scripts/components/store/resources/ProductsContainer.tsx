import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import Button from '@/components/elements/Button';
import purchaseResource from '@/api/store/purchaseResource';
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

export default () => {
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const cost = useStoreState(state => state.storefront.data!.cost);

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
            <h3 css={tw`text-2xl text-neutral-500`}>Buy more resources to add to your server.</h3>
            <Container css={tw`lg:grid lg:grid-cols-3 my-10`}>
                <TitledGreyBox title={'Purchase CPU'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Cpu size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('cpu')}
                        >
                            +50% CPU
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase CPU to improve server performance.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per 50% CPU: {cost.cpu} JCR</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('memory')}
                        >
                            +1GB RAM
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase RAM to improve server performance.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per 1GB RAM: {cost.memory} JCR</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('disk')}
                        >
                            +1GB DISK
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase disk space to improve server capacity.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per 1GB disk: {cost.disk} JCR</p>
                </TitledGreyBox>
            </Container>
            <Container css={tw`lg:grid lg:grid-cols-4 my-10`}>
                <TitledGreyBox title={'Purchase Server Slot'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Server size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('slots')}
                        >
                            +1 slot
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase a server slot to deploy a server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per slot: {cost.slot} JCR</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Ports'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Share2 size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('ports')}
                        >
                            +1 port
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase a port to connect to your server.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per port: {cost.port} JCR</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Backups'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Archive size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('backups')}
                        >
                            +1 backup
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase a backup to protect your data.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per backup slot: {cost.backup} JCR</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Databases'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Database size={40} />
                        <Button
                            isSecondary
                            css={tw`ml-4`}
                            onClick={() => purchase('databases')}
                        >
                            +1 database
                        </Button>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase a database to store data.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per database: {cost.database} JCR</p>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};
