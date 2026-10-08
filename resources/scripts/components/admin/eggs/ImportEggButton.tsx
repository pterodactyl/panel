import { useNavigate } from '@tanstack/react-router';
import { Upload } from 'lucide-react';
import { importAdminEggInput, useImportAdminEgg } from '@/api/admin/eggs/queries';
import EggFileDialog from '@/components/admin/eggs/EggFileDialog';
import { Dialog } from '@/components/elements/dialog';
import { NewButton } from '@/components/elements/NewButton';

export default function ImportEggButton() {
    const navigate = useNavigate();
    const importEgg = useImportAdminEgg();

    const submit = async (file: File) => {
        const egg = await importEgg.mutateAsync(importAdminEggInput({ import_file: file }));

        void navigate({ to: '/panel/eggs/$eggId', params: { eggId: egg.attributes.id } });
    };

    return (
        <Dialog.Trigger
            trigger={({ onClick }) => (
                <NewButton isSecondary icon={Upload} onClick={onClick}>
                    Import egg
                </NewButton>
            )}
        >
            {({ open, onClose }) => (
                <EggFileDialog
                    open={open}
                    onClose={onClose}
                    title='Import egg'
                    description='Upload an egg JSON file to add a server template.'
                    submitLabel='Import Egg'
                    submitting={importEgg.isPending}
                    onSubmit={submit}
                />
            )}
        </Dialog.Trigger>
    );
}
