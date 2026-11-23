import { NewLinkButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

export default function CreateServerButton() {
    const { t } = useTranslation();

    return <NewLinkButton to='/panel/servers/new'>{t('New server')}</NewLinkButton>;
}
