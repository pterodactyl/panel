import { ServerContentBlock } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';
import PageStatus from '../../shared/PageStatus';

export default function FilesPage({ model, className }: ViewProps<'page.server.files'>) {
    // The file manager keeps its toolbar up and shows its own spinner while each directory lists.
    return (
        <PageStatus model={{ ...model, loading: false }} className={className}>
            <ServerContentBlock title='File Manager' className={className}>
                <Slot name='server.files.before' />
                <View name='server.files.manager' />
                <Slot name='server.files.after' />
            </ServerContentBlock>
        </PageStatus>
    );
}
