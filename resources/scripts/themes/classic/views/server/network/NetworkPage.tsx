import { ServerContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function NetworkPage({ model, className }: ViewProps<'page.server.network'>) {
    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title='Network' className={className}>
                <View name='server.allocations' />
            </ServerContentBlock>
        </PageStatus>
    );
}
