import { NewLinkButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

export default function CreateMountButton() {
    const { t } = useTranslation();

    return <NewLinkButton to='/panel/mounts/new'>{t('New mount')}</NewLinkButton>;
}
