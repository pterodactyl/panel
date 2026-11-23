import { PageContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function AccountActivityPage({ className }: ViewProps<'page.account.activity'>) {
    const { t } = useTranslation();

    return (
        <PageContentBlock title={t('Account Activity Log')} className={className}>
            <View name='activity.log' scope='account' />
        </PageContentBlock>
    );
}
