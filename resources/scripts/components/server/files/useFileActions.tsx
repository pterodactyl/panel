import { createContext, use, useMemo, useState, type ReactNode } from 'react';
import { join } from 'pathe';
import RenameFileModal from '@/components/server/files/RenameFileModal';
import ChmodFileModal from '@/components/server/files/ChmodFileModal';
import { useServerDirectory, useServerStore } from '@/state/server';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import type { FileObject } from '@/api/server/files/queries';
import { Dialog } from '@/components/elements/dialog';
import FileActionItems from '@/components/server/files/FileActionItems';
import { useCurrentServerUuid } from '@/api/server/queries';
import {
    compressFilesInput,
    copyFileInput,
    decompressFileInput,
    deleteFilesInput,
    useCompressFiles,
    useCopyFile,
    useDecompressFile,
    useDeleteFiles,
    useFileDownloadUrl,
} from '@/api/server/files/queries';

type ModalType = 'rename' | 'move' | 'chmod' | 'delete';

interface FileRowActions {
    open: (modal: ModalType, file: FileObject) => void;
    copy: (file: FileObject) => void;
    download: (file: FileObject) => void;
    archive: (file: FileObject) => void;
    unarchive: (file: FileObject) => void;
}

const FileRowActionsContext = createContext<FileRowActions | null>(null);

/** Renders the row action dialogs once for every file row below it. */
export function FileRowActionsProvider({ children }: { children: ReactNode }) {
    const [modal, setModal] = useState<ModalType | null>(null);
    const [target, setTarget] = useState<FileObject | null>(null);

    const uuid = useCurrentServerUuid()!;
    const directory = useServerDirectory();
    const removeSelectedFile = useServerStore((state) => state.files.removeSelectedFile);
    const { mutateAsync: deleteFiles } = useDeleteFiles();
    const copyFile = useCopyFile(directory);
    const downloadFile = useFileDownloadUrl();
    const compressFiles = useCompressFiles();
    const decompressFile = useDecompressFile();
    const { mutateAsync: copy } = copyFile;
    const { mutateAsync: download } = downloadFile;
    const { mutateAsync: compress } = compressFiles;
    const { mutateAsync: decompress } = decompressFile;

    const actions = useMemo<FileRowActions>(
        () => ({
            open: (next, file) => {
                setTarget(file);
                setModal(next);
            },
            copy: ({ attributes }) => {
                copy(copyFileInput(uuid, join(directory, attributes.name))).catch(() => {});
            },
            download: ({ attributes }) => {
                download({ uuid, file: join(directory, attributes.name) })
                    .then((url) => {
                        window.location.assign(url);
                    })
                    .catch(() => {});
            },
            archive: ({ attributes }) => {
                compress(compressFilesInput(uuid, directory, [attributes.name])).catch(() => {});
            },
            unarchive: ({ attributes }) => {
                decompress(decompressFileInput(uuid, directory, attributes.name)).catch(() => {});
            },
        }),
        [uuid, directory, copy, download, compress, decompress]
    );

    const close = () => setModal(null);
    const name = target?.attributes.name ?? '';

    const doDeletion = () => {
        deleteFiles(deleteFilesInput(uuid, directory, [name]))
            .then(() => removeSelectedFile({ directory, name }))
            .catch(() => {});
    };

    const showSpinner =
        copyFile.isPending || downloadFile.isPending || compressFiles.isPending || decompressFile.isPending;

    return (
        <FileRowActionsContext.Provider value={actions}>
            {children}
            <Dialog.Confirm
                open={modal === 'delete'}
                onClose={close}
                title={`Delete ${target?.attributes.is_file === false ? 'Directory' : 'File'}`}
                confirm='Delete'
                onConfirmed={() => {
                    close();
                    doDeletion();
                }}
            >
                You will not be able to recover the contents of&nbsp;
                <span className='font-semibold text-foreground'>{name}</span> once deleted.
            </Dialog.Confirm>
            {target && modal === 'chmod' && (
                <ChmodFileModal
                    key={name}
                    open
                    files={[{ file: name, mode: target.attributes.mode_bits ?? '' }]}
                    onClose={close}
                />
            )}
            {target && (modal === 'rename' || modal === 'move') && (
                <RenameFileModal
                    key={`${modal}:${name}`}
                    open
                    files={[name]}
                    useMoveTerminology={modal === 'move'}
                    onClose={close}
                />
            )}
            <SpinnerOverlay visible={showSpinner} fixed size='large' />
        </FileRowActionsContext.Provider>
    );
}

export default function useFileActions(file: FileObject) {
    const actions = use(FileRowActionsContext);

    if (!actions) {
        throw new Error('FileRowActionsProvider is missing from the component tree.');
    }

    const items = (
        <FileActionItems
            file={file}
            onRename={() => actions.open('rename', file)}
            onMove={() => actions.open('move', file)}
            onChmod={() => actions.open('chmod', file)}
            onCopy={() => actions.copy(file)}
            onUnarchive={() => actions.unarchive(file)}
            onArchive={() => actions.archive(file)}
            onDownload={() => actions.download(file)}
            onDelete={() => actions.open('delete', file)}
        />
    );

    return { items };
}
