import type { Actions } from 'easy-peasy';
import { useStoreActions } from 'easy-peasy';
import { useState } from 'react';
import tw from 'twin.macro';

import deleteAllocation from '@/api/admin/nodes/allocations/deleteAllocation';
import Button from '@/components/elements/Button';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import type { ApplicationStore } from '@/state';

interface Props {
    nodeId: number;
    allocationId: number;
    onDeleted?: () => void;
}

export default ({ nodeId, allocationId, onDeleted }: Props) => {
    const [visible, setVisible] = useState(false);
    const [loading, setLoading] = useState(false);

    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );

    const onDelete = () => {
        setLoading(true);
        clearFlashes('allocation');

        deleteAllocation(nodeId, allocationId)
            .then(() => {
                setLoading(false);
                setVisible(false);
                if (onDeleted !== undefined) {
                    onDeleted();
                }
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'allocation', error });

                setLoading(false);
                setVisible(false);
            });
    };

    return (
        <>
            <ConfirmationModal
                visible={visible}
                title={'Delete allocation?'}
                buttonText={'Yes, delete allocation'}
                onConfirmed={onDelete}
                showSpinnerOverlay={loading}
                onModalDismissed={() => setVisible(false)}
            >
                Are you sure you want to delete this allocation?
            </ConfirmationModal>

            <Button type={'button'} size={'inline'} color={'red'} onClick={() => setVisible(true)}>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    css={tw`h-5 w-5`}
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={2}
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                </svg>
            </Button>
        </>
    );
};
