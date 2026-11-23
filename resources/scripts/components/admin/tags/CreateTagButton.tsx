import { Dialog } from '@/components/elements/dialog';
import { NewButton } from '@/components/elements/NewButton';
import TagFormDialog from '@/components/admin/tags/TagFormDialog';
import { useTranslation } from '@/sdk/localization';

export default function CreateTagButton() {
    const { t } = useTranslation();

    return (
        <Dialog.Trigger trigger={({ onClick }) => <NewButton onClick={onClick}>{t('New tag')}</NewButton>}>
            {({ open, onClose }) => <TagFormDialog open={open} onClose={onClose} />}
        </Dialog.Trigger>
    );
}
