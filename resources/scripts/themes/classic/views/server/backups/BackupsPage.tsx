import { ServerContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function BackupsPage({ model, className }: ViewProps<'page.server.backups'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    if (model.loading) {
        return (
            <div className={className}>
                <Spinner size='large' centered />
            </div>
        );
    }

    return (
        <ServerContentBlock title='Backups' className={className}>
            <View name='server.backups' />
        </ServerContentBlock>
    );
}
