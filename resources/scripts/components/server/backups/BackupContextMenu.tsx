import { useState } from 'react';
import { CloudDownload, Lock, PackageOpen, Unlock } from 'lucide-react';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import Can from '@/components/elements/Can';
import { DeleteAction, RowActions, RowActionsMenu } from '@/components/elements/table/RowActions';
import Checkbox from '@/components/ui/Checkbox';
import { Dialog } from '@/components/elements/dialog';
import { useCurrentServerUuid } from '@/api/server/queries';
import {
    backupDownloadUrlInput,
    deleteServerBackupInput,
    restoreServerBackupInput,
    type ServerBackup,
    toggleServerBackupLockInput,
    useBackupDownloadUrl,
    useDeleteServerBackup,
    useRestoreServerBackup,
    useToggleServerBackupLock,
} from '@/api/server/backups/queries';

interface Props {
    backup: ServerBackup;
    page: number;
}

const BackupContextMenu = ({ backup, page }: Props) => {
    const uuid = useCurrentServerUuid()!;
    const { attributes } = backup;
    const [modal, setModal] = useState('');
    const [truncate, setTruncate] = useState(false);
    const downloadBackup = useBackupDownloadUrl();
    const deleteBackup = useDeleteServerBackup(backup);
    const restoreBackup = useRestoreServerBackup(backup);
    const toggleBackupLock = useToggleServerBackupLock(page, backup);
    const [isDownloading, setIsDownloading] = useState(false);

    const loading =
        isDownloading ||
        downloadBackup.isPending ||
        deleteBackup.isPending ||
        restoreBackup.isPending ||
        toggleBackupLock.isPending;

    const doDownload = async () => {
        setIsDownloading(true);

        try {
            const url = await downloadBackup.mutateAsync(backupDownloadUrlInput(uuid, backup));
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`Download failed with status ${response.status}`);
            }

            const blobUrl = URL.createObjectURL(await response.blob());
            const a = document.createElement('a');

            a.href = blobUrl;
            a.download = `backup-${attributes.name || backup.attributes.uuid}.tar.gz`;
            a.click();

            URL.revokeObjectURL(blobUrl);
        } catch (error) {
            console.error('Download error:', error);
        } finally {
            setIsDownloading(false);
        }
    };

    const doDeletion = () => {
        deleteBackup
            .mutateAsync(deleteServerBackupInput(uuid, backup))
            .then(() => setModal(''))
            .catch(() => {});
    };

    const doRestorationAction = () => {
        restoreBackup
            .mutateAsync(restoreServerBackupInput(uuid, backup, truncate))
            .then(() => setModal(''))
            .catch(() => {});
    };

    const onLockToggle = () => {
        if (attributes.is_locked && modal !== 'unlock') {
            return setModal('unlock');
        }

        toggleBackupLock.mutate(toggleServerBackupLockInput(uuid, backup), { onSuccess: () => setModal('') });
    };

    return (
        <>
            <Dialog.Confirm
                open={modal === 'unlock'}
                onClose={() => setModal('')}
                title={`Unlock "${attributes.name}"`}
                pending={toggleBackupLock.isPending}
                onConfirmed={onLockToggle}
            >
                This backup will no longer be protected from automated or accidental deletions.
            </Dialog.Confirm>
            <Dialog.Confirm
                open={modal === 'restore'}
                onClose={() => setModal('')}
                confirm='Restore'
                title={`Restore "${attributes.name}"`}
                pending={restoreBackup.isPending}
                onConfirmed={() => doRestorationAction()}
            >
                <p>
                    Your server will be stopped. You will not be able to control the power state, access the file
                    manager, or create additional backups until completed.
                </p>
                <p className='mt-4 -mb-2 bg-card p-3 rounded-sm'>
                    <label htmlFor='restore_truncate' className='text-base flex items-center cursor-pointer'>
                        <Checkbox
                            className='mr-2 h-5 w-5 data-[checked]:border-destructive data-[checked]:bg-destructive data-[checked]:text-destructive-foreground'
                            id='restore_truncate'
                            value='true'
                            checked={truncate}
                            onChange={() => setTruncate((s) => !s)}
                        />
                        Delete all files before restoring backup.
                    </label>
                </p>
            </Dialog.Confirm>
            <Dialog.Confirm
                title={`Delete "${attributes.name}"`}
                confirm='Continue'
                open={modal === 'delete'}
                pending={deleteBackup.isPending}
                onClose={() => setModal('')}
                onConfirmed={doDeletion}
            >
                This is a permanent operation. The backup cannot be recovered once deleted.
            </Dialog.Confirm>
            <SpinnerOverlay visible={loading} fixed />
            <RowActions>
                <Can action='backup.delete'>
                    <DeleteAction
                        aria-label={`Delete ${attributes.name}`}
                        disabled={attributes.is_locked}
                        disabledReason='Unlock this backup before deleting it.'
                        onClick={() => setModal('delete')}
                    />
                </Can>
                {attributes.is_successful && (
                    <RowActionsMenu label={`More actions for ${attributes.name}`}>
                        <Can action='backup.download'>
                            <DropdownMenu.Item onClick={doDownload} icon={CloudDownload}>
                                Download
                            </DropdownMenu.Item>
                        </Can>
                        <Can action='backup.restore'>
                            <DropdownMenu.Item onClick={() => setModal('restore')} icon={PackageOpen}>
                                Restore
                            </DropdownMenu.Item>
                        </Can>
                        <Can action='backup.delete'>
                            <DropdownMenu.Item onClick={onLockToggle} icon={attributes.is_locked ? Unlock : Lock}>
                                {attributes.is_locked ? 'Unlock' : 'Lock'}
                            </DropdownMenu.Item>
                        </Can>
                    </RowActionsMenu>
                )}
            </RowActions>
        </>
    );
};

export default BackupContextMenu;
