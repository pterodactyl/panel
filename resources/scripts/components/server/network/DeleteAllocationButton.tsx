import tw from 'twin.macro';
import React, { useState } from 'react';
import useFlash from '@/plugins/useFlash';
import Icon from '@/components/elements/Icon';
import { ServerContext } from '@/state/server';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import getServerAllocations from '@/api/swr/getServerAllocations';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import deleteServerAllocation from '@/api/server/network/deleteServerAllocation';

interface Props {
    allocation: number;
}

const DeleteAllocationButton = ({ allocation }: Props) => {
    const [ confirm, setConfirm ] = useState(false);

    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    const { mutate } = getServerAllocations();
    const { clearFlashes, clearAndAddHttpError } = useFlash();

    const deleteAllocation = () => {
        clearFlashes('server:network');

        mutate(data => data?.filter(a => a.id !== allocation), false);
        setServerFromState(s => ({ ...s, allocations: s.allocations.filter(a => a.id !== allocation) }));

        deleteServerAllocation(uuid, allocation)
            .catch(error => clearAndAddHttpError({ key: 'server:network', error }));
    };

    return (
        <>
            <ConfirmationModal
                visible={confirm}
                title={'Remove this allocation?'}
                buttonText={'Delete'}
                onConfirmed={deleteAllocation}
                onModalDismissed={() => setConfirm(false)}
            >
                This allocation will be immediately removed from your server. Are you sure you want to continue?
            </ConfirmationModal>
            <button
                css={tw`text-neutral-400 px-2 py-1 mr-2 transition-colors duration-150 hover:text-red-400`}
                type={'button'}
                onClick={() => setConfirm(true)}
            >
                <Icon icon={faTrashAlt} css={tw`w-3 h-auto`}/>
            </button>
        </>
    );
};

export default DeleteAllocationButton;
