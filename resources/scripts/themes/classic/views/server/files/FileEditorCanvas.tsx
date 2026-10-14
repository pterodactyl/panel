import { cn, SpinnerOverlay } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

export default function FileEditorCanvas({ model, className }: ViewProps<'server.files.editor.canvas'>) {
    return (
        <div className={cn('relative', className)} aria-busy={model.saving}>
            <SpinnerOverlay visible={model.saving} />
            <model.Editor />
        </div>
    );
}
