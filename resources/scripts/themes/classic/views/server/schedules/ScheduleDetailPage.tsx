import { PageContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function ScheduleDetailPage({ model, className }: ViewProps<'page.server.schedules.detail'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <PageContentBlock title='Schedules' className={className}>
            {model.loading ? (
                <Spinner size='large' centered />
            ) : (
                <View name='server.schedule'>
                    <View name='server.schedule.tasks' className='rounded-t-none' />
                </View>
            )}
        </PageContentBlock>
    );
}
