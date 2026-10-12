import { ServerContentBlock, ServerError } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';

export default function FilesPage({ model }: ViewProps<'page.server.files'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} />;
    }

    return (
        <ServerContentBlock title='File Manager'>
            <Slot name='server.files.before' />
            <View name='server.files.manager' />
            <Slot name='server.files.after' />
        </ServerContentBlock>
    );
}
