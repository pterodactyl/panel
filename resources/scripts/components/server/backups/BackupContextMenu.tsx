import React, { useState } from 'react';
import {
    faBoxOpen,
    faCloudDownloadAlt,
    faEllipsisH,
    faLock,
    faTrashAlt,
    faUnlock,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import DropdownMenu, { DropdownButtonRow } from '@/components/elements/DropdownMenu';
import getBackupDownloadUrl from '@/api/server/backups/getBackupDownloadUrl';
import useFlash from '@/plugins/useFlash';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import deleteBackup from '@/api/server/backups/deleteBackup';
import ConfirmationModal from '@/components/elements/ConfirmationModal';
import Can from '@/components/elements/Can';
import tw from 'twin.macro';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerBackup } from '@/api/server/types';
import { ServerContext } from '@/state/server';
import Input from '@/components/elements/Input';
import { restoreServerBackup } from '@/api/server/backups';
import http, { httpErrorToHuman } from '@/api/http';
import { useTranslation } from 'react-i18next';

interface Props {
    backup: ServerBackup;
}

export default ({ backup }: Props) => {
    const { t } = useTranslation();
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);
    const [ modal, setModal ] = useState('');
    const [ loading, setLoading ] = useState(false);
    const [ truncate, setTruncate ] = useState(false);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { mutate } = getServerBackups();

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
            .then(() => mutate(data => ({
                ...data,
                items: data.items.filter(b => b.uuid !== backup.uuid),
                backupCount: data.backupCount - 1,
            }), false))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
                setLoading(false);
                setModal('');
            });
    };

    const doRestorationAction = () => {
        setLoading(true);
        clearFlashes('backups');
        restoreServerBackup(uuid, backup.uuid, truncate)
            .then(() => setServerFromState(s => ({
                ...s,
                status: 'restoring_backup',
            })))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'backups', error });
            })
            .then(() => setLoading(false))
            .then(() => setModal(''));
    };

    const onLockToggle = () => {
        if (backup.isLocked && modal !== 'unlock') {
            return setModal('unlock');
        }

        http.post(`/api/client/servers/${uuid}/backups/${backup.uuid}/lock`)
            .then(() => mutate(data => ({
                ...data,
                items: data.items.map(b => b.uuid !== backup.uuid ? b : {
                    ...b,
                    isLocked: !b.isLocked,
                }),
            }), false))
            .catch(error => alert(httpErrorToHuman(error)))
            .then(() => setModal(''));
    };

    return (
        <>
            <ConfirmationModal
                visible={modal === 'unlock'}
                title={t('Unlock Backup')}
                onConfirmed={onLockToggle}
                onModalDismissed={() => setModal('')}
                buttonText={t('Confirmation Unlock')}
            >
                {t('Confirmation Unlock Backup')}
            </ConfirmationModal>
            <ConfirmationModal
                visible={modal === 'restore'}
                title={t('Restore Backup')}
                buttonText={t('Restore Button')}
                onConfirmed={() => doRestorationAction()}
                onModalDismissed={() => setModal('')}
            >
                <p css={tw`text-neutral-300`}>
                    {t('Stop Server')}
                </p>
                <p css={tw`text-neutral-300 mt-4`}>
                    {t('Confirmation Stop Server')}
                </p>
                <p css={tw`mt-4 -mb-2 bg-neutral-900 p-3 rounded`}>
                    <label
                        htmlFor={'restore_truncate'}
                        css={tw`text-base text-neutral-200 flex items-center cursor-pointer`}
                    >
                        <Input
                            type={'checkbox'}
                            css={tw`text-red-500! w-5! h-5! mr-2`}
                            id={'restore_truncate'}
                            value={'true'}
                            checked={truncate}
                            onChange={() => setTruncate(s => !s)}
                        />
                        {t('Remove All Files')}
                    </label>
                </p>
            </ConfirmationModal>
            <ConfirmationModal
                visible={modal === 'delete'}
                title={t('Delete Backup')}
                buttonText={t('Confirmation Delete Backup')}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setModal('')}
            >
                {t('Delete Backup Confirmation')}
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
                            <DropdownButtonRow onClick={doDownload}>
                                <FontAwesomeIcon fixedWidth icon={faCloudDownloadAlt} css={tw`text-xs`}/>
                                <span css={tw`ml-2`}>{t('Download')}</span>
                            </DropdownButtonRow>
                        </Can>
                        <Can action={'backup.restore'}>
                            <DropdownButtonRow onClick={() => setModal('restore')}>
                                <FontAwesomeIcon fixedWidth icon={faBoxOpen} css={tw`text-xs`}/>
                                <span css={tw`ml-2`}>{t('Restore')}</span>
                            </DropdownButtonRow>
                        </Can>
                        <Can action={'backup.delete'}>
                            <>
                                <DropdownButtonRow onClick={onLockToggle}>
                                    <FontAwesomeIcon
                                        fixedWidth
                                        icon={backup.isLocked ? faUnlock : faLock}
                                        css={tw`text-xs mr-2`}
                                    />
                                    {backup.isLocked ? 'Unlock' : 'Lock'}
                                </DropdownButtonRow>
                                {!backup.isLocked &&
                                <DropdownButtonRow danger onClick={() => setModal('delete')}>
                                    <FontAwesomeIcon fixedWidth icon={faTrashAlt} css={tw`text-xs`}/>
                                    <span css={tw`ml-2`}>{t('Delete')}</span>
                                </DropdownButtonRow>
                                }
                            </>
                        </Can>
                    </div>
                </DropdownMenu>
                :
                <button
                    onClick={() => setModal('delete')}
                    css={tw`text-neutral-200 transition-colors duration-150 hover:text-neutral-100 p-2`}
                >
                    <FontAwesomeIcon icon={faTrashAlt}/>
                </button>
            }
        </>
    );
};
