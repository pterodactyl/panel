import { NewLinkButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

export default function CreateUserButton() {
    const { t } = useTranslation();

    return <NewLinkButton to='/panel/users/new'>{t('New user')}</NewLinkButton>;
}
