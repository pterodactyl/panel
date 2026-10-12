import { ServerContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

export default function ServerActivityPage({ className }: ViewProps<'page.server.activity'>) {
    return (
        <ServerContentBlock title='Activity Log' className={className}>
            <View name='activity.log' scope='server' />
        </ServerContentBlock>
    );
}
