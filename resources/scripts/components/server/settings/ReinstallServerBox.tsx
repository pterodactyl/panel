import React, { useState } from 'react';
import { ServerContext } from '@/state/server';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import reinstallServer from '@/api/server/reinstallServer';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';

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
                addFlash({ key: 'settings', type: 'success', message: 'Your server has begun the reinstallation process.' });
            })
            .catch(error => {
                console.error(error);

                addFlash({ key: 'settings', type: 'error', message: httpErrorToHuman(error) });
            })
            .then(() => {
                setIsSubmitting(false);
                setModalVisible(false);
            });
    }

    return (
        <TitledGreyBox title={'Reinstall Server'} className={'relative'}>
            <ConfirmationModal
                title={'Confirm server reinstallation'}
                buttonText={'Yes, reinstall server'}
                onConfirmed={() => reinstall()}
                showSpinnerOverlay={isSubmitting}
                visible={modalVisible}
                onDismissed={() => setModalVisible(false)}
            >
                Your server will be stopped and some files may be deleted or modified during this process, are you sure you wish to continue?
            </ConfirmationModal>
            <p className={'text-sm'}>
                Reinstalling your server will stop it, and then re-run the installation script that initially
                set it up. <strong className={'font-medium'}>Some files may be deleted or modified during this process,
                please back up your data before continuing.</strong>
            </p>
            <div className={'mt-6 text-right'}>
                <button
                    type={'button'}
                    className={'btn btn-sm btn-secondary btn-red'}
                    onClick={() => setModalVisible(true)}
                >
                    Reinstall Server
                </button>
            </div>
        </TitledGreyBox>
    );
};
