import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function DatabasesPage({ model }: ViewProps<'page.server.databases'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <ServerContentBlock title='Databases'>
            <View name='server.databases' />
        </ServerContentBlock>
    );
}
