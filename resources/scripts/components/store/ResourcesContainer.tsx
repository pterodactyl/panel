import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import * as Icon from 'react-feather';
import { Link } from 'react-router-dom';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import { Button } from '@/components/elements/button';
import { Dialog } from '@/components/elements/dialog';
import purchaseResource from '@/api/store/purchaseResource';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import PurchaseBox from '@/components/elements/store/PurchaseBox';
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
    const [open, setOpen] = useState(false);
    const [resource, setResource] = useState('');
    const { addFlash, clearFlashes, clearAndAddHttpError } = useFlash();
    const cost = useStoreState((state) => state.storefront.data!.cost);

    const purchase = (resource: string) => {
        clearFlashes('store:resources');

        purchaseResource(resource)
            .then(() => {
                setOpen(false);
                addFlash({
                    type: 'success',
                    key: 'store:resources',
                    message: 'Resource has been added to your account.',
                });
            })
            .catch((error) => clearAndAddHttpError({ key: 'store:resources', error }));
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
                Are you sure you want to purchase more {resource}? This will take credits from your account and add the
                resource. This is not a reversible transaction.
            </Dialog.Confirm>
            <div className={'my-10'}>
                <Link to={'/store'}>
                    <Button.Text className={'w-full lg:w-1/6 m-2'}>
                        <Icon.ArrowLeft className={'mr-1'} />
                        Return to Storefront
                    </Button.Text>
                </Link>
            </div>
            <h1 className={'j-left text-5xl'}>Order resources</h1>
            <h3 className={'j-left text-2xl text-neutral-500'}>Buy more resources to add to your server.</h3>
            <Container className={'j-up lg:grid lg:grid-cols-4 my-10 gap-8'}>
                <PurchaseBox
                    type={'cpu'}
                    amount={50}
                    suffix={'%'}
                    cost={cost.cpu}
                    setOpen={setOpen}
                    icon={<Icon.Cpu />}
                    setResource={setResource}
                    description={'Buy CPU to improve server load times and performance.'}
                />
                <PurchaseBox
                    type={'memory'}
                    amount={1}
                    suffix={'GB'}
                    cost={cost.memory}
                    setOpen={setOpen}
                    icon={<Icon.PieChart />}
                    setResource={setResource}
                    description={'Buy RAM to improve overall server performance.'}
                />
                <PurchaseBox
                    type={'disk'}
                    amount={1}
                    suffix={'GB'}
                    cost={cost.disk}
                    setOpen={setOpen}
                    icon={<Icon.HardDrive />}
                    setResource={setResource}
                    description={'Buy disk to store more files.'}
                />
                <PurchaseBox
                    type={'slots'}
                    amount={1}
                    cost={cost.slot}
                    setOpen={setOpen}
                    icon={<Icon.Server />}
                    setResource={setResource}
                    description={'Buy a server slot so you can deploy a new server.'}
                />
            </Container>
            <Container className={'j-up lg:grid lg:grid-cols-4 my-10 gap-8'}>
                <PurchaseBox
                    type={'ports'}
                    amount={1}
                    cost={cost.port}
                    setOpen={setOpen}
                    icon={<Icon.Share2 />}
                    setResource={setResource}
                    description={'Buy a network port to add to a server.'}
                />
                <PurchaseBox
                    type={'backups'}
                    amount={1}
                    cost={cost.backup}
                    setOpen={setOpen}
                    icon={<Icon.Archive />}
                    setResource={setResource}
                    description={'Buy a backup to keep your data secure.'}
                />
                <PurchaseBox
                    type={'databases'}
                    amount={1}
                    cost={cost.database}
                    setOpen={setOpen}
                    icon={<Icon.Database />}
                    setResource={setResource}
                    description={'Buy a database to get and set data.'}
                />
                <TitledGreyBox title={'How to use resources'}>
                    <p className={'font-semibold'}>Adding to an existing server</p>
                    <p className={'text-xs text-gray-500'}>
                        If you have a server that is already deployed, you can add resources to it by going to the
                        &apos;edit&apos; tab.
                    </p>
                    <p className={'font-semibold mt-1'}>Adding to a new server</p>
                    <p className={'text-xs text-gray-500'}>
                        You can buy resources and add them to a new server in the server creation page, which you can
                        access via the store.
                    </p>
                </TitledGreyBox>
            </Container>
        </PageContentBlock>
    );
};
