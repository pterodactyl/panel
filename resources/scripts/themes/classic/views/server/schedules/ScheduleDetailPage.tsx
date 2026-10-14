import { PageContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function ScheduleDetailPage({ model, className }: ViewProps<'page.server.schedules.detail'>) {
    return (
        <PageStatus model={model} className={className}>
            <PageContentBlock title='Schedules' className={className}>
                <View name='server.schedule'>
                    <View name='server.schedule.tasks' className='rounded-t-none' />
                </View>
            </PageContentBlock>
        </PageStatus>
    );
}
