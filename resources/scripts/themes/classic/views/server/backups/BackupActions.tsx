import { useState } from 'react';
import { CloudDownload, Lock, PackageOpen, Unlock } from 'lucide-react';
import {
    Checkbox,
    DeleteAction,
    Dialog,
    DropdownMenuItem,
    RowActions,
    RowActionsMenu,
    type BackupListModel,
    type BackupSummary,
} from '@/sdk';

type Confirming = '' | 'unlock' | 'restore' | 'delete';

/** A backup's delete action and its menu of download, restore and lock. */
export default function BackupActions({ model, backup }: { model: BackupListModel; backup: BackupSummary }) {
    const [confirming, setConfirming] = useState<Confirming>('');
    const [truncate, setTruncate] = useState(false);
    const { actions, pending, permissions } = model;
    const close = () => setConfirming('');
    const closeOnSuccess = (done: boolean) => {
        if (done) {
            close();
        }
    };

    const onLockToggle = () => {
        if (backup.locked && confirming !== 'unlock') {
            setConfirming('unlock');

            return;
        }

        void actions.toggleLock(backup.uuid).then(closeOnSuccess);
    };

    return (
        <>
            <Dialog.Confirm
                open={confirming === 'unlock'}
                onClose={close}
                title={`Unlock "${backup.name}"`}
                pending={pending.lock}
                onConfirmed={onLockToggle}
            >
                This backup will no longer be protected from automated or accidental deletions.
            </Dialog.Confirm>
            <Dialog.Confirm
                open={confirming === 'restore'}
                onClose={close}
                confirm='Restore'
                title={`Restore "${backup.name}"`}
                pending={pending.restore}
                onConfirmed={() => void actions.restore(backup.uuid, truncate).then(closeOnSuccess)}
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
                            onChange={() => setTruncate((checked) => !checked)}
                        />
                        Delete all files before restoring backup.
                    </label>
                </p>
            </Dialog.Confirm>
            <Dialog.Confirm
                title={`Delete "${backup.name}"`}
                confirm='Continue'
                open={confirming === 'delete'}
                pending={pending.remove}
                onClose={close}
                onConfirmed={() => void actions.remove(backup.uuid).then(closeOnSuccess)}
            >
                This is a permanent operation. The backup cannot be recovered once deleted.
            </Dialog.Confirm>
            <RowActions>
                {permissions.delete && (
                    <DeleteAction
                        aria-label={`Delete ${backup.name}`}
                        disabled={backup.locked}
                        disabledReason='Unlock this backup before deleting it.'
                        onClick={() => setConfirming('delete')}
                    />
                )}
                {backup.successful && (
                    <RowActionsMenu label={`More actions for ${backup.name}`}>
                        {permissions.download && (
                            <DropdownMenuItem onClick={() => void actions.download(backup.uuid)} icon={CloudDownload}>
                                Download
                            </DropdownMenuItem>
                        )}
                        {permissions.restore && (
                            <DropdownMenuItem onClick={() => setConfirming('restore')} icon={PackageOpen}>
                                Restore
                            </DropdownMenuItem>
                        )}
                        {permissions.delete && (
                            <DropdownMenuItem onClick={onLockToggle} icon={backup.locked ? Unlock : Lock}>
                                {backup.locked ? 'Unlock' : 'Lock'}
                            </DropdownMenuItem>
                        )}
                    </RowActionsMenu>
                )}
            </RowActions>
        </>
    );
}
