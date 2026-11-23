import { NewLinkButton } from '@/components/elements/NewButton';
import { useTranslation } from '@/sdk/localization';

export default function CreateNodeButton() {
    const { t } = useTranslation();

    return <NewLinkButton to='/panel/nodes/new'>{t('New node')}</NewLinkButton>;
}
