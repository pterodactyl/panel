import tw from 'twin.macro';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import Input from '@/components/elements/Input';
import React, { useEffect, useState } from 'react';
import deleteServer from '@/api/server/deleteServer';
import { Actions, useStoreActions } from 'easy-peasy';
import { Dialog } from '@/components/elements/dialog';
import { Button } from '@/components/elements/button/index';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

export default () => {
    const [password, setPassword] = useState('');
    const [modalVisible, setModalVisible] = useState(false);
    const [passwordConfirm, setPasswordConfirm] = useState(false);
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        e.stopPropagation();

        clearFlashes('settings');

        deleteServer(uuid, password)
            .then(() => {
                addFlash({
                    key: 'settings',
                    type: 'success',
                    message: 'Your server has been deleted.',
                });
            })
            .catch((error) => {
                console.error(error);

                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            })
            .then(() => setModalVisible(false));
    };

    useEffect(() => {
        clearFlashes();
    }, []);

    return (
        <TitledGreyBox title={'Delete Server'} css={tw`relative`}>
            <Dialog.Confirm
                open={modalVisible}
                title={'Confirm server deletion'}
                confirm={'Yes, delete server'}
                onClose={() => setModalVisible(false)}
                onConfirmed={() => {
                    setPasswordConfirm(true);
                    setModalVisible(false);
                }}
            >
                Your server will be deleted, with all files being purged and the server&apos;s resources being returned
                to your account. Are you sure you wish to continue?
            </Dialog.Confirm>
            <form id={'delete-server-form'} onSubmit={submit}>
                <Dialog
                    open={passwordConfirm}
                    title={'Elevated request requires password confirmation'}
                    onClose={() => setPasswordConfirm(false)}
                >
                    <p className={'mt-2 mb-1 text-gray-400'}>Enter password to continue with server deletion.</p>
                    <Input
                        className={'mt-1'}
                        type={'password'}
                        value={password}
                        onChange={(e) => setPassword(e.currentTarget.value)}
                    />
                    <Button disabled={!password.length} type={'submit'} className={'mt-2'} form={'delete-server-form'}>
                        Confirm
                    </Button>
                </Dialog>
            </form>
            <p css={tw`text-sm`}>
                Deleting your server will shut down any processes, return the resources to your account and delete all
                files associated with the instance - as well as backups, databases and settings.
                <strong css={tw`font-medium`}>
                    All data will be permenantly lost if you continue with this action.
                </strong>
            </p>
            <div css={tw`mt-6 text-right`}>
                <Button.Danger variant={Button.Variants.Secondary} onClick={() => setModalVisible(true)}>
                    Delete Server
                </Button.Danger>
            </div>
        </TitledGreyBox>
    );
};
