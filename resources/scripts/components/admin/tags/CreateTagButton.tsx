import { Dialog } from '@/components/elements/dialog';
import { NewButton } from '@/components/elements/NewButton';
import TagFormDialog from '@/components/admin/tags/TagFormDialog';

export default function CreateTagButton() {
    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>New tag</NewButton>}>
            {({ open, onClose }) => <TagFormDialog open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
