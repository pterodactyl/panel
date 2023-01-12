import { useEffect, useState } from 'react';

import compressFiles from '@/api/server/files/compressFiles';
import deleteFiles from '@/api/server/files/deleteFiles';
import { Button } from '@/components/elements/button';
import { Dialog } from '@/components/elements/dialog';
import Portal from '@/components/elements/Portal';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import RenameFileModal from '@/components/server/files/RenameFileModal';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import useFlash from '@/plugins/useFlash';
import { ServerContext } from '@/state/server';
import FadeTransition from '@/components/elements/transitions/FadeTransition';

const MassActionsBar = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);

    const { mutate } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const [loading, setLoading] = useState(false);
    const [loadingMessage, setLoadingMessage] = useState('');
    const [showConfirm, setShowConfirm] = useState(false);
    const [showMove, setShowMove] = useState(false);
    const directory = ServerContext.useStoreState(state => state.files.directory);

    const selectedFiles = ServerContext.useStoreState(state => state.files.selectedFiles);
    const setSelectedFiles = ServerContext.useStoreActions(actions => actions.files.setSelectedFiles);

    useEffect(() => {
        if (!loading) setLoadingMessage('');
    }, [loading]);

    const onClickCompress = () => {
        setLoading(true);
        clearFlashes('files');
        setLoadingMessage('Archiving files...');

        compressFiles(uuid, directory, selectedFiles)
            .then(() => mutate())
            .then(() => setSelectedFiles([]))
            .catch(error => clearAndAddHttpError({ key: 'files', error }))
            .then(() => setLoading(false));
    };

    const onClickConfirmDeletion = () => {
        setLoading(true);
        setShowConfirm(false);
        clearFlashes('files');
        setLoadingMessage('Deleting files...');

        deleteFiles(uuid, directory, selectedFiles)
            .then(async () => {
                await mutate(files => files!.filter(f => selectedFiles.indexOf(f.name) < 0), false);
                setSelectedFiles([]);
            })
            .catch(async error => {
                await mutate();
                clearAndAddHttpError({ key: 'files', error });
            })
            .then(() => setLoading(false));
    };

    return (
        <>
            <div className="pointer-events-none fixed bottom-0 left-0 right-0 z-20 flex justify-center">
                <SpinnerOverlay visible={loading} size={'large'} fixed>
                    {loadingMessage}
                </SpinnerOverlay>
                <Dialog.Confirm
                    title={'Delete Files'}
                    open={showConfirm}
                    confirm={'Delete'}
                    onClose={() => setShowConfirm(false)}
                    onConfirmed={onClickConfirmDeletion}
                >
                    <p className="mb-2">
                        Are you sure you want to delete&nbsp;
                        <span className="font-semibold text-slate-50">{selectedFiles.length} files</span>? This is a
                        permanent action and the files cannot be recovered.
                    </p>
                    {selectedFiles.slice(0, 15).map(file => (
                        <li key={file}>{file}</li>
                    ))}
                    {selectedFiles.length > 15 && <li>and {selectedFiles.length - 15} others</li>}
                </Dialog.Confirm>
                {showMove && (
                    <RenameFileModal
                        files={selectedFiles}
                        visible
                        appear
                        useMoveTerminology
                        onDismissed={() => setShowMove(false)}
                    />
                )}
                <Portal>
                    <div className="fixed bottom-0 z-50 mb-6 flex w-full justify-center">
                        <FadeTransition duration="duration-75" show={selectedFiles.length > 0} appear unmount>
                            <div className="pointer-events-auto flex items-center space-x-4 rounded bg-black/50 p-4">
                                <Button onClick={() => setShowMove(true)}>Move</Button>
                                <Button onClick={onClickCompress}>Archive</Button>
                                <Button.Danger variant={Button.Variants.Secondary} onClick={() => setShowConfirm(true)}>
                                    Delete
                                </Button.Danger>
                            </div>
                        </FadeTransition>
                    </div>
                </Portal>
            </div>
        </>
    );
};

export default MassActionsBar;
