import { ServerContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function BackupsPage({ model }: ViewProps<'page.server.backups'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    if (model.loading) {
        return <Spinner size='large' centered />;
    }

    return (
        <ServerContentBlock title='Backups'>
            <View name='server.backups' />
        </ServerContentBlock>
    );
}
