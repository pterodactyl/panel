import { ServerContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function NetworkPage({ model, className }: ViewProps<'page.server.network'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title={t('Network')} className={className}>
                <View name='server.allocations' />
            </ServerContentBlock>
        </PageStatus>
    );
}
