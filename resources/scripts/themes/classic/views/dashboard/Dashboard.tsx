import { PageContentBlock } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../shared/PageStatus';

export default function Dashboard({ model, className }: ViewProps<'page.dashboard'>) {
    return (
        <PageStatus model={model} className={className}>
            <PageContentBlock title='Dashboard' className={className}>
                <View name='dashboard.serverList' />
            </PageContentBlock>
        </PageStatus>
    );
}
