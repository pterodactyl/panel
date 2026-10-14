import { ServerContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function BackupsPage({ model, className }: ViewProps<'page.server.backups'>) {
    return (
        <PageStatus model={model} className={className}>
            <ServerContentBlock title='Backups' className={className}>
                <View name='server.backups' />
            </ServerContentBlock>
        </PageStatus>
    );
}
