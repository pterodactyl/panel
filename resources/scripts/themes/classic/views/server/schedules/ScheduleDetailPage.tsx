import { PageContentBlock, useTranslation } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function ScheduleDetailPage({ model, className }: ViewProps<'page.server.schedules.detail'>) {
    const { t } = useTranslation();

    return (
        <PageStatus model={model} className={className}>
            <PageContentBlock title={t('Schedules')} className={className}>
                <View name='server.schedule'>
                    <View name='server.schedule.tasks' className='rounded-t-none' />
                </View>
            </PageContentBlock>
        </PageStatus>
    );
}
