import { useDeleteSSHKey } from '@/api/account/ssh-keys/queries';
import { Dialog } from '@/components/elements/dialog';
import Code from '@/components/elements/Code';
import { DeleteAction } from '@/components/elements/table/RowActions';

export default function DeleteSSHKeyButton({ name, fingerprint }: { name: string; fingerprint: string }) {
    const deleteSshKey = useDeleteSSHKey();

    const onClick = (close: () => void) => {
        deleteSshKey.mutate({ body: { fingerprint } }, { onSettled: close });
    };

    return (
        <Dialog.ConfirmTrigger
            title='Delete SSH Key'
            confirm='Delete Key'
            pending={deleteSshKey.isPending}
            onConfirmed={(_event, close) => onClick(close)}
            trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${name}`} onClick={onClick} />}
        >
            Removing the <Code>{name}</Code> SSH key will invalidate its usage across the Panel.
        </Dialog.ConfirmTrigger>
    );
}
