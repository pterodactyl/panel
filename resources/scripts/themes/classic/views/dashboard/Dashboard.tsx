import { PageContentBlock, ServerError } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function Dashboard({ model, className }: ViewProps<'page.dashboard'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <PageContentBlock title='Dashboard' className={className}>
            <View name='dashboard.serverList' />
        </PageContentBlock>
    );
}
