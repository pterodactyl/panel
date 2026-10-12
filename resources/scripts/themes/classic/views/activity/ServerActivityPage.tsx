import { ServerContentBlock } from '@/sdk';
import { View } from '@/sdk/theme';

export default function ServerActivityPage() {
    return (
        <ServerContentBlock title='Activity Log'>
            <View name='activity.log' scope='server' />
        </ServerContentBlock>
    );
}
