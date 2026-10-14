import { Button, cn, Dialog, Portal, SpinnerOverlay } from '@/sdk';
import { Slot, type FileManagerModel, type ViewProps } from '@/sdk/theme';
import { usePending } from '../usePending';
import { RenameDialog } from './FileDialogs';

function MassActions({ model, className }: { model: FileManagerModel; className?: string }) {
    const [pending, run] = usePending();
    const { selection, actions } = model;

    return (
        <div className={cn('pointer-events-none fixed bottom-0 z-20 left-0 right-0 flex justify-center', className)}>
            <SpinnerOverlay visible={pending} size='large' fixed />
            <Portal>
                <div className='pointer-events-none fixed bottom-0 mb-6 flex justify-center w-full z-50'>
                    {selection.length > 0 && (
                        <div className='flex items-center space-x-4 pointer-events-auto rounded-sm p-4 bg-background/50'>
                            <Slot name='server.files.selectionActions' data={model.slot} />
                            <Dialog.Trigger trigger={({ onClick }) => <Button onClick={onClick}>Move</Button>}>
                                {({ open, onClose }) =>
                                    open && (
                                        <RenameDialog
                                            model={model}
                                            files={[...selection]}
                                            open={open}
                                            move
                                            onClose={onClose}
                                        />
                                    )
                                }
                            </Dialog.Trigger>
                            <Button onClick={() => run(actions.archive(selection))}>Archive</Button>
                            <Dialog.ConfirmTrigger
                                title='Delete Files'
                                confirm='Delete'
                                trigger={({ onClick }) => (
                                    <Button.Danger isSecondary onClick={onClick}>
                                        Delete
                                    </Button.Danger>
                                )}
                                onConfirmed={(_event, close) => {
                                    close();
                                    run(actions.remove(selection));
                                }}
                            >
                                <p className='mb-2'>
                                    Are you sure you want to delete&nbsp;
                                    <span className='font-semibold text-foreground'>{selection.length} files</span>?
                                    This is a permanent action and the files cannot be recovered.
                                </p>
                                <ul className='mt-2 mb-0 pl-6 list-disc list-outside'>
                                    {selection.slice(0, 15).map((file) => (
                                        <li key={file}>{file}</li>
                                    ))}
                                    {selection.length > 15 && <li>and {selection.length - 15} others</li>}
                                </ul>
                            </Dialog.ConfirmTrigger>
                        </div>
                    )}
                </div>
            </Portal>
        </div>
    );
}

export default function FileManagerSelection({ model, className }: ViewProps<'server.files.manager.selection'>) {
    return model.entries.length > 0 ? <MassActions model={model} className={className} /> : null;
}
