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
import { useTranslation } from 'react-i18next';

export default () => {
    const { t } = useTranslation();
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
        <TitledGreyBox title={t('Settings Reinstall Server Title')} css={tw`relative`}>
            <ConfirmationModal
                title={t('Settings Confirmation Reinstall')}
                buttonText={t('Settings Confirm Reinstall')}
                onConfirmed={reinstall}
                showSpinnerOverlay={isSubmitting}
                visible={modalVisible}
                onModalDismissed={() => setModalVisible(false)}
            >
                {t('Settings Reinstall Server Desc')}
            </ConfirmationModal>
            <p css={tw`text-sm`}>
                {t('Settings Reinstall Desc 1')} <strong css={tw`font-medium`}> {t('Settings Reinstall Desc 2')}</strong>
            </p>
            <div css={tw`mt-6 text-right`}>
                <Button
                    type={'button'}
                    color={'red'}
                    isSecondary
                    onClick={() => setModalVisible(true)}
                >
                    {t('Settings Reinstall Button')}
                </Button>
            </div>
        </TitledGreyBox>
    );
};
