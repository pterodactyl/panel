import type { ReplacementProps } from '@pterodactyl/sdk';

// The panel owns the buffer, the save and the unsaved-changes guard; this adds a status line and keeps every native part.
export default function FileEditor({ Default, model }: ReplacementProps<'server.files.editor'>) {
    return (
        <>
            {model.dirty && (
                <p role='status' className='hw:mb-2 hw:text-sm hw:text-muted-foreground'>
                    {model.name || 'This new file'} has unsaved changes.
                </p>
            )}
            <Default />
        </>
    );
}
