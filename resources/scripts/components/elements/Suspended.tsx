import tw from 'twin.macro';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import { useStoreState } from '@/state/hooks';
import Code from '@/components/elements/Code';
import { ServerContext } from '@/state/server';
import Input from '@/components/elements/Input';
import renewServer from '@/api/server/renewServer';
import deleteServer from '@/api/server/deleteServer';
import { Button } from '@/components/elements/button';
import { Dialog } from '@/components/elements/dialog';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import FlashMessageRender from '@/components/FlashMessageRender';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default () => {
    const [name, setName] = useState('');

    const [isSubmit, setSubmit] = useState(false);
    const [renewDialog, setRenewDialog] = useState(false);
    const [deleteDialog, setDeleteDialog] = useState(false);
    const [confirmDialog, setConfirmDialog] = useState(false);

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const store = useStoreState((state) => state.storefront.data!);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const serverName = ServerContext.useStoreState((state) => state.server.data!.name);
    const renewable = ServerContext.useStoreState((state) => state.server.data?.renewable);

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

    const doDeletion = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        e.stopPropagation();

        clearFlashes('server:renewal');
        setSubmit(true);

        deleteServer(uuid, name)
            .then(() => {
                setSubmit(false);
                // @ts-expect-error this is valid
                window.location = '/store';
            })
            .catch((error) => {
                clearAndAddHttpError({ key: 'server:renewal', error });
                setSubmit(false);
            });
    };

    return (
        <>
            <Dialog.Confirm
                open={renewDialog}
                onClose={() => setRenewDialog(false)}
                title={'Confirm server renewal'}
                confirm={'Continue'}
                onConfirmed={() => doRenewal()}
            >
                <SpinnerOverlay visible={isSubmit} />
                Are you sure you want to spend {store.renewals.cost} credits to renew your server?
            </Dialog.Confirm>
            <Dialog.Confirm
                open={deleteDialog}
                onClose={() => setDeleteDialog(false)}
                title={'Confirm server deletion'}
                confirm={'Continue'}
                onConfirmed={() => setConfirmDialog(true)}
            >
                <SpinnerOverlay visible={isSubmit} />
                This action will remove your server from the system, along with all files and configurations.
            </Dialog.Confirm>
            <form id={'delete-suspended-server-form'} onSubmit={doDeletion}>
                <Dialog open={confirmDialog} title={'Confirm server deletion'} onClose={() => setConfirmDialog(false)}>
                    {name !== serverName && (
                        <>
                            <p className={'my-2 text-gray-400'}>
                                Type <Code>{serverName}</Code> below.
                            </p>
                            <Input type={'text'} value={name} onChange={(n) => setName(n.target.value)} />
                        </>
                    )}
                    <Button
                        disabled={name !== serverName}
                        type={'submit'}
                        className={'mt-2'}
                        form={'delete-suspended-server-form'}
                    >
                        Confirm
                    </Button>
                </Dialog>
            </form>
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
                                <Button
                                    className={'mx-2 my-1'}
                                    onClick={() => setRenewDialog(true)}
                                    disabled={isSubmit}
                                >
                                    Renew Now
                                </Button>
                                <Button.Danger
                                    className={'mx-2 my-1'}
                                    onClick={() => setDeleteDialog(true)}
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
