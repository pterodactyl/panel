import { Dialog } from '@/components/elements/dialog';
import { DeleteAction } from '@/components/elements/table/RowActions';
import { useCurrentServerUuid } from '@/api/server/queries';
import {
    deleteServerAllocationInput,
    type ServerAllocation,
    useDeleteServerAllocation,
} from '@/api/server/network/queries';
import { ip } from '@/lib/formatters';

interface Props {
    allocation: ServerAllocation;
}

const DeleteAllocationButton = ({ allocation }: Props) => {
    const uuid = useCurrentServerUuid()!;
    const deleteAllocationMutation = useDeleteServerAllocation();
    const { attributes } = allocation;
    const address = `${attributes.ip_alias || ip(attributes.ip)}:${attributes.port}`;

    const deleteAllocation = (close: () => void) => {
        deleteAllocationMutation.mutate(deleteServerAllocationInput(uuid, attributes.id), { onSuccess: close });
    };

    return (
        <Dialog.ConfirmTrigger
            title={'Remove Allocation'}
            confirm={'Delete'}
            pending={deleteAllocationMutation.isPending}
            onConfirmed={(_event, close) => deleteAllocation(close)}
            trigger={({ onClick }) => <DeleteAction aria-label={`Delete allocation ${address}`} onClick={onClick} />}
        >
            This allocation will be immediately removed from your server.
        </Dialog.ConfirmTrigger>
    );
};

export default DeleteAllocationButton;
