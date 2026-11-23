import { NewLinkButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

export default function CreateDatabaseHostButton() {
    const { t } = useTranslation();

    return <NewLinkButton to='/panel/databases/new'>{t('New database host')}</NewLinkButton>;
}
