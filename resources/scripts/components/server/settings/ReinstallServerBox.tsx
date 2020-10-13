import React, { useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import reinstallServer from '@/api/server/reinstallServer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const [ isSubmitting, setIsSubmitting ] = useState(false);
    const [ modalVisible, setModalVisible ] = useState(false);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const reinstall = () => {
        clearFlashes('settings');
        setIsSubmitting(true);
        reinstallServer(uuid)
            .then(() => {
                addFlash({
                    key: 'settings',
                    type: 'success',
                    message: 'Your server has begun the reinstallation process.',
                });
            })
            .catch(error => {
                console.error(error);

                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setIsSubmitting(false);
                setModalVisible(false);
            });
    };

    useEffect(() => {
        clearFlashes();
    }, []);

    return (
        <TitledGreyBox title={'Reinstall Server'} css={tw`relative`}>
            <ConfirmationModal
                title={'Confirm server reinstallation'}
                buttonText={'Yes, reinstall server'}
                onConfirmed={reinstall}
                showSpinnerOverlay={isSubmitting}
                visible={modalVisible}
                onModalDismissed={() => setModalVisible(false)}
            >
                Your server will be stopped and some files may be deleted or modified during this process, are you sure
                you wish to continue?
            </ConfirmationModal>
            <p css={tw`text-sm`}>
                Reinstalling your server will stop it, and then re-run the installation script that initially
                set it up.&nbsp;
                <strong css={tw`font-medium`}>
                    Some files may be deleted or modified during this process, please back up your data before
                    continuing.
                </strong>
            </p>
            <div css={tw`mt-6 text-right`}>
                <Button
                    type={'button'}
                    color={'red'}
                    isSecondary
                    onClick={() => setModalVisible(true)}
                >
                    Reinstall Server
                </Button>
            </div>
        </TitledGreyBox>
    );
};
