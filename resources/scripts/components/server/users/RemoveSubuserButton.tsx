import React, { useState } from 'react';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import { Subuser } from '@/state/server/subusers';
import deleteSubuser from '@/api/server/users/deleteSubuser';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import tw from 'twin.macro';
import { useTranslation } from 'react-i18next';

export default ({ subuser }: { subuser: Subuser }) => {
    const { t } = useTranslation();
    const [ loading, setLoading ] = useState(false);
    const [ showConfirmation, setShowConfirmation ] = useState(false);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const removeSubuser = ServerContext.useStoreActions(actions => actions.subusers.removeSubuser);
    const { addError, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    const doDeletion = () => {
        setLoading(true);
        clearFlashes('users');
        deleteSubuser(uuid, subuser.uuid)
            .then(() => {
                setLoading(false);
                removeSubuser(subuser.uuid);
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'users', message: httpErrorToHuman(error) });
                setShowConfirmation(false);
            });
    };

    return (
        <>
            <ConfirmationModal
                title={t('Users Deletion Sub User Title')}
                buttonText={t('Users Deletion Sub User Button')}
                visible={showConfirmation}
                showSpinnerOverlay={loading}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setShowConfirmation(false)}
            >
                {t('Users Deletion Sub User Desc')}
            </ConfirmationModal>
            <button
                type={'button'}
                aria-label={'Delete subuser'}
                css={tw`block text-sm p-2 text-neutral-500 hover:text-red-600 transition-colors duration-150`}
                onClick={() => setShowConfirmation(true)}
            >
                <FontAwesomeIcon icon={faTrashAlt}/>
            </button>
        </>
    );
};
