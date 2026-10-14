import { cn } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

/** The browser's pieces are views of their own, so a theme replaces one and keeps the others. */
export default function FileManager({ className, model }: ViewProps<'server.files.manager'>) {
    return (
        <div className={cn('min-w-0', className)}>
            <View name='server.files.manager.toolbar' model={model} />
            <View name='server.files.manager.list' model={model} />
            <View name='server.files.manager.selection' model={model} />
        </div>
    );
}
