import { PageContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function ScheduleDetailPage({ model }: ViewProps<'page.server.schedules.detail'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <PageContentBlock title='Schedules'>
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
