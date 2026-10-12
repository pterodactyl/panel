import { ServerContentBlock, ServerError, Spinner } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function UsersPage({ model }: ViewProps<'page.server.users'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    if (model.loading) {
        return <Spinner size='large' centered />;
    }

    return (
        <ServerContentBlock title='Users'>
            <View name='server.subusers' />
        </ServerContentBlock>
    );
}
