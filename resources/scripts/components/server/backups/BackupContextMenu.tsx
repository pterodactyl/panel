import React, { useState } from 'react';
import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { faCloudDownloadAlt, faEllipsisH, faLock, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
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
import tw from 'twin.macro';

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
                appear
                visible={visible}
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
            <SpinnerOverlay visible={loading} fixed/>
            <DropdownMenu
                renderToggle={onClick => (
                    <button
                        onClick={onClick}
                        css={tw`text-neutral-200 transition-colors duration-150 hover:text-neutral-100 p-2`}
                    >
                        <FontAwesomeIcon icon={faEllipsisH}/>
                    </button>
                )}
            >
                <div css={tw`text-sm`}>
                    <Can action={'backup.download'}>
                        <DropdownButtonRow onClick={() => doDownload()}>
                            <FontAwesomeIcon fixedWidth icon={faCloudDownloadAlt} css={tw`text-xs`}/>
                            <span css={tw`ml-2`}>Download</span>
                        </DropdownButtonRow>
                    </Can>
                    <DropdownButtonRow onClick={() => setVisible(true)}>
                        <FontAwesomeIcon fixedWidth icon={faLock} css={tw`text-xs`}/>
                        <span css={tw`ml-2`}>Checksum</span>
                    </DropdownButtonRow>
                    <Can action={'backup.delete'}>
                        <DropdownButtonRow danger onClick={() => setDeleteVisible(true)}>
                            <FontAwesomeIcon fixedWidth icon={faTrashAlt} css={tw`text-xs`}/>
                            <span css={tw`ml-2`}>Delete</span>
                        </DropdownButtonRow>
                    </Can>
                </div>
            </DropdownMenu>
        </>
    );
};
