import { createContext, useContext } from 'react';
import { Link } from '@tanstack/react-router';
import { FolderOpen } from 'lucide-react';
import { useCurrentServerIdentifier } from '@/api/server/queries';
import { fileObjectKey, type FileObject } from '@/api/server/files/queries';
import { NewButton } from '@/components/elements/NewButton';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import Spinner from '@/components/elements/Spinner';
import Checkbox from '@/components/ui/Checkbox';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import FileManagerBreadcrumbs from '@/components/server/files/FileManagerBreadcrumbs';
import FileManagerStatus from '@/components/server/files/FileManagerStatus';
import FileObjectRow from '@/components/server/files/FileObjectRow';
import MassActionsBar from '@/components/server/files/MassActionsBar';
import NewDirectoryButton from '@/components/server/files/NewDirectoryButton';
import UploadButton from '@/components/server/files/UploadButton';
import { FileRowActionsProvider } from '@/components/server/files/useFileActions';
import Slot from '@/extensions/Slot';
import { encodePathSegments } from '@/helpers';
import { cn } from '@/lib/cn';
import type {
    ComponentPartProps,
    ComponentParts,
    DefaultComponentProps,
    FileManagerModel,
} from '@/extensions/componentTypes';
import useFileManagerExtensionData from './useFileManagerExtensionData';

export interface FileManagerSession {
    model: FileManagerModel;
    files: readonly FileObject[];
}
export const FileManagerContext = createContext<FileManagerSession | null>(null);

const managerActionsClass = [
    'grid grid-cols-2 sm:grid-cols-3 w-full gap-4 mb-4',
    'md:flex md:flex-1 md:justify-end md:mb-0',
    '[&_button]:w-full [&_button:first-child]:col-span-2 sm:[&_button:first-child]:col-span-1 md:[&_button]:w-auto',
].join(' ');

type PartProps = ComponentPartProps<'server.files.manager'>;
function Toolbar({ model }: PartProps) {
    const id = useCurrentServerIdentifier()!;
    const extensionData = useFileManagerExtensionData();
    return (
        <ErrorBoundary>
            <div className={'flex flex-wrap-reverse md:flex-nowrap mb-4'}>
                <FileManagerBreadcrumbs>
                    <Checkbox
                        className={'mx-4'}
                        aria-label={'Select all files'}
                        checked={model.entries.length > 0 && model.selection.length === model.entries.length}
                        indeterminate={model.selection.length > 0 && model.selection.length < model.entries.length}
                        onChange={(checked) =>
                            model.actions.select(checked ? model.entries.map((entry) => entry.name) : [])
                        }
                    />
                </FileManagerBreadcrumbs>
                {model.permissions.create && (
                    <div className={managerActionsClass}>
                        <FileManagerStatus />
                        <NewDirectoryButton />
                        <UploadButton />
                        <Link
                            to={'/server/$id/files/$action'}
                            params={{ id, action: 'new' }}
                            hash={encodePathSegments(model.directory)}
                        >
                            <NewButton>New file</NewButton>
                        </Link>
                    </div>
                )}
            </div>
            {extensionData && <Slot name={'server.files.toolbar'} data={extensionData} />}
        </ErrorBoundary>
    );
}
function List({ model }: PartProps) {
    const { files } = useContext(FileManagerContext)!;
    if (model.loading) return <Spinner size={'large'} centered />;
    if (!files.length) {
        return (
            <Empty className={'border'}>
                <EmptyHeader>
                    <EmptyMedia variant={'icon'}>
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
    return (
        <div>
            {model.truncated && (
                <div className={'rounded-sm bg-warning mb-px p-3'}>
                    <p className={'text-warning-foreground text-sm text-center'}>
                        This directory is too large to display in the browser, limiting output to first 250 files.
                    </p>
                </div>
            )}
            <FileRowActionsProvider>
                {files.map((file) => (
                    <FileObjectRow key={fileObjectKey(file)} file={file} />
                ))}
            </FileRowActionsProvider>
        </div>
    );
}
function Selection({ model }: PartProps) {
    return model.entries.length > 0 ? <MassActionsBar selectedFiles={model.selection} /> : null;
}
export const fileManagerParts: ComponentParts<'server.files.manager'> = {
    toolbar: Toolbar,
    list: List,
    selection: Selection,
};
export function DefaultFileManager({ className, parts }: DefaultComponentProps<'server.files.manager'>) {
    const { model } = useContext(FileManagerContext)!;
    const ToolbarPart = parts?.toolbar ?? Toolbar;
    const ListPart = parts?.list ?? List;
    const SelectionPart = parts?.selection ?? Selection;
    return (
        <div className={cn('min-w-0', className)}>
            <ToolbarPart model={model} />
            <ListPart model={model} />
            <SelectionPart model={model} />
        </div>
    );
}
