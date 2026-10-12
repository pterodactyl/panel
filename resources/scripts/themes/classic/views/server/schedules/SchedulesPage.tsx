import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function SchedulesPage({ model }: ViewProps<'page.server.schedules'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <ServerContentBlock title='Schedules'>
            <View name='server.schedules' />
        </ServerContentBlock>
    );
}
