import React, { useState } from 'react';
import { faCloudDownloadAlt, faEllipsisH, faLock, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
import getBackupDownloadUrl from '@/api/server/backups/getBackupDownloadUrl';
import useFlash from '@/plugins/useFlash';
import ChecksumModal from '@/components/server/backups/ChecksumModal';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import deleteBackup from '@/api/server/backups/deleteBackup';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import Can from '@/components/elements/Can';
import tw from 'twin.macro';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerBackup } from '@/api/server/types';
import { ServerContext } from '@/state/server';
import { useTranslation } from 'react-i18next';

interface Props {
    backup: ServerBackup;
}

export default ({ backup }: Props) => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const [ loading, setLoading ] = useState(false);
    const [ visible, setVisible ] = useState(false);
    const [ deleteVisible, setDeleteVisible ] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerBackups();
    const { t } = useTranslation('server');

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
                clearAndAddHttpError({ key: 'backups', error });
            })
            .then(() => setLoading(false));
    };

    const doDeletion = () => {
        setLoading(true);
        clearFlashes('backups');
        deleteBackup(uuid, backup.uuid)
            .then(() => {
                mutate(data => ({
                    ...data,
                    items: data.items.filter(b => b.uuid !== backup.uuid),
                }), false);
            })
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
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
                checksum={backup.checksum}
            />
            }
            <ConfirmationModal
                visible={deleteVisible}
                title={t('delete_backup_question')}
                buttonText={t('delete_backup_yes')}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setDeleteVisible(false)}
            >
                {t('delete_backup_demand')}
            </ConfirmationModal>
            <SpinnerOverlay visible={loading} fixed/>
            {backup.isSuccessful ?
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
                                <span css={tw`ml-2`}>{t('download')}</span>
                            </DropdownButtonRow>
                        </Can>
                        <DropdownButtonRow onClick={() => setVisible(true)}>
                            <FontAwesomeIcon fixedWidth icon={faLock} css={tw`text-xs`}/>
                            <span css={tw`ml-2`}>{t('checksum')}</span>
                        </DropdownButtonRow>
                        <Can action={'backup.delete'}>
                            <DropdownButtonRow danger onClick={() => setDeleteVisible(true)}>
                                <FontAwesomeIcon fixedWidth icon={faTrashAlt} css={tw`text-xs`}/>
                                <span css={tw`ml-2`}>{t('delete')}</span>
                            </DropdownButtonRow>
                        </Can>
                    </div>
                </DropdownMenu>
                :
                <button
                    onClick={() => setDeleteVisible(true)}
                    css={tw`text-neutral-200 transition-colors duration-150 hover:text-neutral-100 p-2`}
                >
                    <FontAwesomeIcon icon={faTrashAlt}/>
                </button>
            }
        </>
    );
};
