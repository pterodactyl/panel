import React, { useState } from 'react';
import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { faEllipsisH } from '@fortawesome/free-solid-svg-icons/faEllipsisH';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
import { faCloudDownloadAlt } from '@fortawesome/free-solid-svg-icons/faCloudDownloadAlt';
import { faTrashAlt } from '@fortawesome/free-solid-svg-icons/faTrashAlt';
import { faLock } from '@fortawesome/free-solid-svg-icons/faLock';
import getBackupDownloadUrl from '@/api/server/backups/getBackupDownloadUrl';
import { httpErrorToHuman } from '@/api/http';
import useFlash from '@/plugins/useFlash';
import ChecksumModal from '@/components/server/backups/ChecksumModal';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useServer from '@/plugins/useServer';
import deleteBackup from '@/api/server/backups/deleteBackup';
import { ServerContext } from '@/state/server';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import Can from '@/components/elements/Can';

interface Props {
    backup: ServerBackup;
}

export default ({ backup }: Props) => {
    const { uuid } = useServer();
    const [ loading, setLoading ] = useState(false);
    const [ visible, setVisible ] = useState(false);
    const [ deleteVisible, setDeleteVisible ] = useState(false);
    const { addError, clearFlashes } = useFlash();
    const removeBackup = ServerContext.useStoreActions(actions => actions.backups.removeBackup);

    const doDownload = () => {
        setLoading(true);
        clearFlashes('backups');
        getBackupDownloadUrl(uuid, backup.uuid)
            .then(url => {
                // @ts-ignore
                window.location = url;
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'backups', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    const doDeletion = () => {
        setLoading(true);
        clearFlashes('backups');
        deleteBackup(uuid, backup.uuid)
            .then(() => removeBackup(backup.uuid))
            .catch(error => {
                console.error(error);
                addError({ key: 'backups', message: httpErrorToHuman(error) });
                setLoading(false);
                setDeleteVisible(false);
            });
    };

    return (
        <>
            {visible &&
            <ChecksumModal
                visible={visible}
                appear={true}
                onDismissed={() => setVisible(false)}
                checksum={backup.sha256Hash}
            />
            }
            {deleteVisible &&
            <ConfirmationModal
                title={'Delete this backup?'}
                buttonText={'Yes, delete backup'}
                onConfirmed={() => doDeletion()}
                visible={deleteVisible}
                onDismissed={() => setDeleteVisible(false)}
            >
                Are you sure you wish to delete this backup? This is a permanent operation and the backup cannot
                be recovered once deleted.
            </ConfirmationModal>
            }
            <SpinnerOverlay visible={loading} fixed={true}/>
            <DropdownMenu
                renderToggle={onClick => (
                    <button
                        onClick={onClick}
                        className={'text-neutral-200 transition-color duration-150 hover:text-neutral-100 p-2'}
                    >
                        <FontAwesomeIcon icon={faEllipsisH}/>
                    </button>
                )}
            >
                <div className={'text-sm'}>
                    <Can action={'backup.download'}>
                        <DropdownButtonRow onClick={() => doDownload()}>
                            <FontAwesomeIcon fixedWidth={true} icon={faCloudDownloadAlt} className={'text-xs'}/>
                            <span className={'ml-2'}>Download</span>
                        </DropdownButtonRow>
                    </Can>
                    <DropdownButtonRow onClick={() => setVisible(true)}>
                        <FontAwesomeIcon fixedWidth={true} icon={faLock} className={'text-xs'}/>
                        <span className={'ml-2'}>Checksum</span>
                    </DropdownButtonRow>
                    <Can action={'backup.delete'}>
                        <DropdownButtonRow danger={true} onClick={() => setDeleteVisible(true)}>
                            <FontAwesomeIcon fixedWidth={true} icon={faTrashAlt} className={'text-xs'}/>
                            <span className={'ml-2'}>Delete</span>
                        </DropdownButtonRow>
                    </Can>
                </div>
            </DropdownMenu>
        </>
    );
};
