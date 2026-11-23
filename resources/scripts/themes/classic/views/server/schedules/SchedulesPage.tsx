import { ServerContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function SchedulesPage({ model, className }: ViewProps<'page.server.schedules'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title={t('Schedules')} className={className}>
                <View name='server.schedules' />
            </ServerContentBlock>
        </PageStatus>
    );
}
