import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function NetworkPage({ model, className }: ViewProps<'page.server.network'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <ServerContentBlock title='Network' className={className}>
            <View name='server.allocations' />
        </ServerContentBlock>
    );
}
