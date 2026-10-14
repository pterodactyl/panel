import { ServerContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function DatabasesPage({ model, className }: ViewProps<'page.server.databases'>) {
    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title='Databases' className={className}>
                <View name='server.databases' />
            </ServerContentBlock>
        </PageStatus>
    );
}
