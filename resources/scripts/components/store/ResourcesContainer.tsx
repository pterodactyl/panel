import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';
import purchaseResource from '@/api/store/purchaseResource';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
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
    const [open, setOpen] = useState(false);
    const [resource, setResource] = useState('');

    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const cost = useStoreState((state) => state.storefront.data!.cost);

    const purchase = (resource: string) => {
        clearFlashes('store:resources');

        purchaseResource(resource)
            .then(() => setOpen(false))
            .then(() =>
                addFlash({
                    type: 'success',
                    key: 'store:resources',
                    message: 'Resource has been added to your account.',
                })
            )
            .catch((error) => {
                clearAndAddHttpError({ key: 'store:resources', error });
            });
    };

    return (
        <PageContentBlock title={'Store Products'} showFlashKey={'store:resources'}>
            <SpinnerOverlay size={'large'} visible={open} />
            <Dialog.Confirm
                open={open}
                onClose={() => setOpen(false)}
                title={'Confirm resource seletion'}
                confirm={'Continue'}
                onConfirmed={() => purchase(resource)}
            >
                Are you sure you want to purchase this resource? This will take credits from your account and add the
                resource. This is not a reversible transaction.
            </Dialog.Confirm>
            <h1 className={'j-left text-5xl'}>Order resources</h1>
            <h3 className={'j-left text-2xl text-neutral-500'}>Buy more resources to add to your server.</h3>
            <Container className={'j-up lg:grid lg:grid-cols-3 my-10'}>
                <TitledGreyBox title={'Purchase CPU'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Cpu size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('cpu');
                            }}
                        >
                            +50% CPU
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase CPU to improve server performance.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per 50% CPU: {cost.cpu} credits</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase RAM'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.PieChart size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('memory');
                            }}
                        >
                            +1GB RAM
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase RAM to improve server performance.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Cost per 1GB RAM: {cost.memory} credits
                    </p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Disk'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.HardDrive size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('disk');
                            }}
                        >
                            +1GB DISK
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase disk space to improve server capacity.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Cost per 1GB disk: {cost.disk} credits
                    </p>
                </TitledGreyBox>
            </Container>
            <Container className={'j-up lg:grid lg:grid-cols-4 my-10'}>
                <TitledGreyBox title={'Purchase Server Slot'} css={tw`mt-8 sm:mt-0`}>
                    <Wrapper>
                        <Icon.Server size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('slots');
                            }}
                        >
                            +1 slot
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase a server slot to deploy a server.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per slot: {cost.slot} credits</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Ports'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Share2 size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('ports');
                            }}
                        >
                            +1 port
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase a port to connect to your server.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Cost per port: {cost.port} credits</p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Backups'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Archive size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('backups');
                            }}
                        >
                            +1 backup
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Purchase a backup to protect your data.
                    </p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Cost per backup slot: {cost.backup} credits
                    </p>
                </TitledGreyBox>
                <TitledGreyBox title={'Purchase Server Databases'} css={tw`mt-8 sm:mt-0 sm:ml-8`}>
                    <Wrapper>
                        <Icon.Database size={40} />
                        <Button.Success
                            variant={Button.Variants.Secondary}
                            css={tw`ml-4`}
                            onClick={() => {
                                setOpen(true);
                                setResource('databases');
                            }}
                        >
                            +1 database
                        </Button.Success>
                    </Wrapper>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>Purchase a database to store data.</p>
                    <p css={tw`mt-1 text-gray-500 text-xs flex justify-center`}>
                        Cost per database: {cost.database} credits
                    </p>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};
