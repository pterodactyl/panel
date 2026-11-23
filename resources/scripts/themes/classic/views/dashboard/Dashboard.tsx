import { PageContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../shared/PageStatus';

export default function Dashboard({ model, className }: ViewProps<'page.dashboard'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <PageContentBlock title={t('Dashboard')} className={className}>
                <View name='dashboard.serverList' />
            </PageContentBlock>
        </PageStatus>
    );
}
