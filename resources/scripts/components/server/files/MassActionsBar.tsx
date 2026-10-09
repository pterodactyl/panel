import Button from '@/components/elements/Button';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import { useCurrentServerUuid } from '@/api/server/queries';
import { useServerDirectory, useServerStore } from '@/state/server';
import RenameFileModal from '@/components/server/files/RenameFileModal';
import Portal from '@/components/elements/Portal';
import Slot from '@/extensions/Slot';
import useFileManagerExtensionData from './useFileManagerExtensionData';
import { Dialog } from '@/components/elements/dialog';
import { compressFilesInput, deleteFilesInput, useCompressFiles, useDeleteFiles } from '@/api/server/files/queries';

/** `selectedFiles` must be entries of the current listing. */
const MassActionsBar = ({ selectedFiles }: { selectedFiles: readonly string[] }) => {
    const uuid = useCurrentServerUuid()!;

    const directory = useServerDirectory();

    const clearSelectedFiles = useServerStore((state) => state.files.clearSelectedFiles);
    const compressFiles = useCompressFiles();
    const deleteFiles = useDeleteFiles();

    const onClickCompress = () => {
        compressFiles
            .mutateAsync(compressFilesInput(uuid, directory, [...selectedFiles]))
            .then(() => clearSelectedFiles())
            .catch(() => {});
    };

    const onClickConfirmDeletion = (close: () => void) => {
        close();

        deleteFiles
            .mutateAsync(deleteFilesInput(uuid, directory, [...selectedFiles]))
            .then(() => {
                clearSelectedFiles();
            })
            .catch(() => {});
    };

    const loading = compressFiles.isPending || deleteFiles.isPending;
    const extensionData = useFileManagerExtensionData();

    return (
        <div className='pointer-events-none fixed bottom-0 z-20 left-0 right-0 flex justify-center'>
            <SpinnerOverlay visible={loading} size='large' fixed />
            <Portal>
                <div className='pointer-events-none fixed bottom-0 mb-6 flex justify-center w-full z-50'>
                    {selectedFiles.length > 0 && (
                        <div className='flex items-center space-x-4 pointer-events-auto rounded-sm p-4 bg-background/50'>
                            {extensionData && <Slot name='server.files.selectionActions' data={extensionData} />}
                            <Dialog.Trigger trigger={({ onClick }) => <Button onClick={onClick}>Move</Button>}>
                                {({ open, onClose }) =>
                                    open && (
                                        <RenameFileModal
                                            files={[...selectedFiles]}
                                            open={open}
                                            useMoveTerminology
                                            onClose={onClose}
                                        />
                                    )
                                }
                            </Dialog.Trigger>
                            <Button onClick={onClickCompress}>Archive</Button>
                            <Dialog.ConfirmTrigger
                                title='Delete Files'
                                confirm='Delete'
                                trigger={({ onClick }) => (
                                    <Button.Danger isSecondary onClick={onClick}>
                                        Delete
                                    </Button.Danger>
                                )}
                                onConfirmed={(_event, close) => onClickConfirmDeletion(close)}
                            >
                                <p className='mb-2 text-center'>
                                    Are you sure you want to delete&nbsp;
                                    <span className='font-semibold text-foreground'>{selectedFiles.length} files</span>?
                                    This is a permanent action and the files cannot be recovered.
                                </p>
                                <ul className='mx-auto mt-2 mb-0 w-fit pl-6 list-disc list-outside text-left'>
                                    {selectedFiles.slice(0, 15).map((file) => (
                                        <li key={file}>{file}</li>
                                    ))}
                                    {selectedFiles.length > 15 && <li>and {selectedFiles.length - 15} others</li>}
                                </ul>
                            </Dialog.ConfirmTrigger>
                        </div>
                    )}
                </div>
            </Portal>
        </div>
    );
};

export default MassActionsBar;
