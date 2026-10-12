import { ServerContentBlock, ServerError } from '@/sdk';
import { Slot, View, type ViewProps } from '@/sdk/theme';

export default function FilesPage({ model, className }: ViewProps<'page.server.files'>) {
    if (model.error !== null) {
        return <ServerError message={model.error} onRetry={model.retry} className={className} />;
    }

    return (
        <ServerContentBlock title='File Manager' className={className}>
            <Slot name='server.files.before' />
            <View name='server.files.manager' />
            <Slot name='server.files.after' />
        </ServerContentBlock>
    );
}
