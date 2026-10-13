import { ServerContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function UsersPage({ model, className }: ViewProps<'page.server.users'>) {
    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title='Users' className={className}>
                <View name='server.subusers' />
            </ServerContentBlock>
        </PageStatus>
    );
}
