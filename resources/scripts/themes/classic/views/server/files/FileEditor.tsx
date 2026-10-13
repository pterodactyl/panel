import { cn } from '@/sdk';
import { View, type ViewProps } from '@/sdk/theme';

/** The editor's pieces are views of their own, so a theme replaces one and keeps the others. */
export default function FileEditor({ className, model }: ViewProps<'server.files.editor'>) {
    return (
        <div className={cn('min-w-0', className)}>
            <View name='server.files.editor.notice' model={model} />
            <View name='server.files.editor.canvas' model={model} />
            <div className='mt-4 flex flex-col gap-3 sm:flex-row sm:items-stretch sm:justify-end'>
                <View name='server.files.editor.language' model={model} />
                <View name='server.files.editor.actions' model={model} />
            </div>
        </div>
    );
}
