import React, { useState } from 'react';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import { Button } from '@/components/elements/button';
import renewServer from '@/api/server/renewServer';
import { ServerContext } from '@/state/server';
import useFlash from '@/plugins/useFlash';
import FlashMessageRender from '@/components/FlashMessageRender';
import deleteServer from '@/api/server/deleteServer';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useStoreState } from '@/state/hooks';
import { Dialog } from '@/components/elements/dialog';

type ModalType = 'renew' | 'delete';

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const renewable = ServerContext.useStoreState((state) => state.server.data?.renewable);
    const store = useStoreState((state) => state.storefront.data!);
    const [isSubmit, setSubmit] = useState(false);
    const [open, setOpen] = useState<ModalType | null>(null);

    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    const doRenewal = () => {
        clearFlashes('server:renewal');
        setSubmit(true);

        renewServer(uuid)
            .then(() => {
                setSubmit(false);
                // @ts-expect-error this is valid
                window.location = '/';
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'server:renewal', error });
                setSubmit(false);
            });
    };

    const doDeletion = () => {
        clearFlashes('server:renewal');
        setSubmit(true);

        deleteServer(uuid)
            .then(() => {
                setSubmit(false)
                // @ts-expect-error this is valid
                window.location = '/store';
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'server:renewal', error });
                setSubmit(false);
            });
    };

    const RenewDialog = () => (
        <Dialog.Confirm
            open={open === 'renew'}
            onClose={() => setOpen(null)}
            title={'Confirm server renewal'}
            confirm={'Continue'}
            onConfirmed={() => doRenewal()}
        >
            <SpinnerOverlay visible={isSubmit} />
            Are you sure you want to spend {store.renewals.cost} {store.currency} to renew your server?
        </Dialog.Confirm>
    );

    const DeleteDialog = () => (
        <Dialog.Confirm
            open={open === 'delete'}
            onClose={() => setOpen(null)}
            title={'Confirm server deletion'}
            confirm={'Continue'}
            onConfirmed={() => doDeletion()}
        >
            <SpinnerOverlay visible={isSubmit} />
            This action will remove your server from the system, along with all files and configurations.
        </Dialog.Confirm>
    );

    return (
        <>
            {open && open === 'renew' ? <RenewDialog /> : <DeleteDialog />}
            <PageContentBlock title={'Server Suspended'}>
                <FlashMessageRender byKey={'server:renewal'} css={tw`mb-1`} />
                <div css={tw`flex justify-center`}>
                    <div
                        css={tw`w-full sm:w-3/4 md:w-1/2 p-12 md:p-20 bg-neutral-900 rounded-lg shadow-lg text-center relative`}
                    >
                        <img src={ServerErrorSvg} css={tw`w-2/3 h-auto select-none mx-auto`} />
                        <h2 css={tw`mt-10 font-bold text-4xl`}>Suspended</h2>
                        {renewable ? (
                            <>
                                <p css={tw`text-sm my-2`}>
                                    Your server has been suspended due to it not being renewed on time. Please click the
                                    &apos;Renew&apos; button in order to reactivate your server. If you want to delete
                                    your server, the resources will automatically be added back to your account so you
                                    can re-deploy a new server easily.
                                </p>
                                <Button className={'mx-2 my-1'} onClick={() => setOpen('renew')} disabled={isSubmit}>
                                    Renew Now
                                </Button>
                                <Button.Danger
                                    className={'mx-2 my-1'}
                                    onClick={() => setOpen('delete')}
                                    disabled={isSubmit}
                                >
                                    Delete Server
                                </Button.Danger>
                            </>
                        ) : (
                            <>This server is suspended and cannot be accessed.</>
                        )}
                    </div>
                </div>
            </PageContentBlock>
        </>
    );
};
