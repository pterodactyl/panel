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

export default ({ subuser }: { subuser: Subuser }) => {
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
                title={'删除此子用户?'}
                buttonText={'确定'}
                visible={showConfirmation}
                showSpinnerOverlay={loading}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setShowConfirmation(false)}
            >
                您确定要删除此子用户吗？ 他们将立即失去对该服务器的所有访问权限。
            </ConfirmationModal>
            <button
                type={'button'}
                aria-label={'删除子用户'}
                css={tw`block text-sm p-2 text-neutral-500 hover:text-red-600 transition-colors duration-150`}
                onClick={() => setShowConfirmation(true)}
            >
                <FontAwesomeIcon icon={faTrashAlt}/>
            </button>
        </>
    );
};
