import { PageContentBlock } from '@/sdk';
import { View } from '@/sdk/theme';

export default function AccountActivityPage() {
    return (
        <PageContentBlock title='Account Activity Log'>
            <View name='activity.log' scope='account' />
        </PageContentBlock>
    );
}
