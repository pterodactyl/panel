import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { DeleteAction } from '@/components/elements/table/RowActions';
import { useCurrentServer } from '@/api/server/queries';
import { deleteServerSubuserInput, useDeleteSubuser } from '@/api/server/users/queries';
import type { Subuser } from '@/api/server/users/queries';

type Props = {
    subuser: Subuser;
};

const RemoveSubuserButton = ({ subuser }: Props) => {
    const server = useCurrentServer();
    const uuid = server?.attributes.uuid ?? '';
    const deleteSubuser = useDeleteSubuser(subuser);
    const loading = deleteSubuser.isPending;

    const doDeletion = (close: () => void) => {
        deleteSubuser
            .mutateAsync(deleteServerSubuserInput(uuid, subuser))
            .catch(() => {})
            .then(close);
    };

    return (
        <Dialog.ConfirmTrigger
            title={'Delete this subuser?'}
            confirm={'Yes, remove subuser'}
            preventExternalClose={loading}
            pending={loading}
            onConfirmed={(_event, close) => doDeletion(close)}
            trigger={({ onClick }) => (
                <DeleteAction aria-label={`Delete ${subuser.attributes.email}`} onClick={onClick} />
            )}
        >
            <SpinnerOverlay visible={loading} />
            Are you sure you wish to remove this subuser? They will have all access to this server revoked immediately.
        </Dialog.ConfirmTrigger>
    );
};

export default RemoveSubuserButton;
