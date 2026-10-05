import type { AdminEgg } from '@/api/admin/eggs/queries';
import { updateAdminEggFromFileInput, useUpdateAdminEggFromFile } from '@/api/admin/eggs/queries';
import EggFileDialog from '@/components/admin/eggs/EggFileDialog';
import { Dialog } from '@/components/elements/dialog';
import Button from '@/components/elements/Button';

interface Props {
    egg: AdminEgg;
}

export default function UpdateEggFromFileButton({ egg }: Props) {
    const updateEggFromFile = useUpdateAdminEggFromFile();

    const submit = async (file: File) => {
        await updateEggFromFile.mutateAsync(updateAdminEggFromFileInput(egg.attributes.id, { import_file: file }));
    };

    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <Button isSecondary onClick={onClick}>
                    Update From File
                </Button>
            )}
        >
            {({ open, onClose }) => (
                <EggFileDialog
                    open={open}
                    onClose={onClose}
                    title={'Update egg from file'}
                    description={
                        "Upload a new egg JSON file to replace this egg's settings. This will not change any existing startup strings or docker images for servers already using this egg."
                    }
                    submitLabel={'Update Egg'}
                    submitColor={'red'}
                    submitting={updateEggFromFile.isPending}
                    onSubmit={submit}
                />
            )}
        </Dialog.Trigger>
    );
}
