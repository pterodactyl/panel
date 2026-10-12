import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function NetworkPage({ model }: ViewProps<'page.server.network'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <ServerContentBlock title='Network'>
            <View name='server.allocations' />
        </ServerContentBlock>
    );
}
