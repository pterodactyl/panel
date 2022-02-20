import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import React, { useState } from 'react';
import { deleteAPIKey, useAPIKeys } from '@/api/account/api-keys';
import { useFlashKey } from '@/plugins/useFlash';
import ConfirmationModal from '@/components/elements/ConfirmationModal';

export default ({ identifier }: { identifier: string }) => {
    const { clearAndAddHttpError } = useFlashKey('account');
    const [ visible, setVisible ] = useState(false);
    const { mutate } = useAPIKeys();

    const onClick = () => {
        clearAndAddHttpError();

        Promise.all([
            mutate((data) => data?.filter((value) => value.identifier !== identifier), false),
            deleteAPIKey(identifier),
        ])
            .catch((error) => {
                mutate(undefined, true);
                clearAndAddHttpError(error);
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={visible}
                title={'Confirm Key Deletion'}
                buttonText={'Yes, Delete Key'}
                onConfirmed={onClick}
                onModalDismissed={() => setVisible(false)}
            >
                Are you sure you wish to delete this API key? All requests using it will immediately be
                invalidated and will fail.
            </ConfirmationModal>
            <button css={tw`ml-4 p-2 text-sm`} onClick={() => setVisible(true)}>
                <FontAwesomeIcon
                    icon={faTrashAlt}
                    css={tw`text-neutral-400 hover:text-red-400 transition-colors duration-150`}
                />
            </button>
        </>
    );
};
