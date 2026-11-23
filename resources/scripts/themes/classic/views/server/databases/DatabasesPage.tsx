import { ServerContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function DatabasesPage({ model, className }: ViewProps<'page.server.databases'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title={t('Databases')} className={className}>
                <View name='server.databases' />
            </ServerContentBlock>
        </PageStatus>
    );
}
