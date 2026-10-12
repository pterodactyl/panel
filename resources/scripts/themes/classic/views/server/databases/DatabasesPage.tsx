import { ServerContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function DatabasesPage({ model, className }: ViewProps<'page.server.databases'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <ServerContentBlock title='Databases' className={className}>
            <View name='server.databases' />
        </ServerContentBlock>
    );
}
