import { FolderOpen } from 'lucide-react';
import {
    Button,
    Checkbox,
    cn,
    Dialog,
    Empty,
    EmptyDescription,
    EmptyHeader,
    EmptyMedia,
    EmptyTitle,
    ErrorBoundary,
    NewButton,
    PanelLink,
    Portal,
    Spinner,
    SpinnerOverlay,
} from '@/sdk';
import { Slot, type FileManagerEntry, type ViewPartProps, type ViewParts, type ViewProps } from '@/sdk/theme';
import { usePending } from '../usePending';
import Breadcrumbs from './Breadcrumbs';
import { NewDirectoryButton, RenameDialog } from './FileDialogs';
import { FileRow, RowActionsProvider } from './FileRows';
import { UploadButton, UploadStatus } from './Uploads';

type PartProps = ViewPartProps<'server.files.manager'>;

const managerActionsClass = [
    'grid grid-cols-2 sm:grid-cols-3 w-full gap-4 mb-4',
    'md:flex md:flex-1 md:justify-end md:mb-0',
    '[&_button]:w-full [&_button:first-child]:col-span-2 sm:[&_button:first-child]:col-span-1 md:[&_button]:w-auto',
].join(' ');

function Toolbar({ model }: PartProps) {
    const { entries, selection } = model;

    return (
        <ErrorBoundary>
            <div className='flex flex-wrap-reverse md:flex-nowrap mb-4'>
                <Breadcrumbs crumbs={model.breadcrumbs}>
                    <Checkbox
                        className='mx-4'
                        aria-label='Select all files'
                        checked={entries.length > 0 && selection.length === entries.length}
                        indeterminate={selection.length > 0 && selection.length < entries.length}
                        onChange={(checked) => model.actions.select(checked ? entries.map((entry) => entry.name) : [])}
                    />
                </Breadcrumbs>
                {model.permissions.create && (
                    <div className={managerActionsClass}>
                        <UploadStatus model={model} />
                        <NewDirectoryButton model={model} />
                        {model.permissions.update && <UploadButton model={model} />}
                        <PanelLink destination={model.newFileDestination}>
                            <NewButton>New file</NewButton>
                        </PanelLink>
                    </div>
                )}
            </div>
            <Slot name='server.files.toolbar' data={model.slot} />
        </ErrorBoundary>
    );
}

const entryKey = ({ kind, name }: FileManagerEntry) => `${kind === 'directory' ? 'dir' : 'file'}_${name}`;

function List({ model }: PartProps) {
    if (model.loading) {
        return <Spinner size='large' centered />;
    }

    if (!model.entries.length) {
        return (
            <Empty className='border'>
                <EmptyHeader>
                    <EmptyMedia variant='icon'>
                        <FolderOpen />
                    </EmptyMedia>
                    <EmptyTitle>This folder is empty</EmptyTitle>
                    <EmptyDescription>
                        {model.permissions.create
                            ? 'Upload files or create a new file or folder to get started.'
                            : 'There are no files in this folder.'}
                    </EmptyDescription>
                </EmptyHeader>
            </Empty>
        );
    }

    const selected = new Set(model.selection);

    return (
        <div>
            {model.truncated && (
                <div className='rounded-sm bg-warning mb-px p-3'>
                    <p className='text-warning-foreground text-sm text-center'>
                        This directory is too large to display in the browser, limiting output to first 250 files.
                    </p>
                </div>
            )}
            <RowActionsProvider model={model}>
                {model.entries.map((entry) => (
                    <FileRow key={entryKey(entry)} entry={entry} selected={selected.has(entry.name)} />
                ))}
            </RowActionsProvider>
        </div>
    );
}

function MassActions({ model }: PartProps) {
    const [pending, run] = usePending();
    const { selection, actions } = model;

    return (
        <div className='pointer-events-none fixed bottom-0 z-20 left-0 right-0 flex justify-center'>
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

function Selection({ model }: PartProps) {
    return model.entries.length > 0 ? <MassActions model={model} /> : null;
}

const fileManagerParts: ViewParts<'server.files.manager'> = { toolbar: Toolbar, list: List, selection: Selection };

export default function FileManager({ className, parts, model }: ViewProps<'server.files.manager'>) {
    return (
        <div className={cn('min-w-0', className)}>
            <parts.toolbar model={model} />
            <parts.list model={model} />
            <parts.selection model={model} />
        </div>
    );
}

FileManager.parts = fileManagerParts;
