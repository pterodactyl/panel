import { PageContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function Dashboard({ model }: ViewProps<'page.dashboard'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <PageContentBlock title='Dashboard'>
            <View name='dashboard.serverList' />
        </PageContentBlock>
    );
}
