import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function SchedulesPage({ model, className }: ViewProps<'page.server.schedules'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <ServerContentBlock title='Schedules' className={className}>
            <View name='server.schedules' />
        </ServerContentBlock>
    );
}
