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

interface Props {
    backup: ServerBackup;
}

export default ({ backup }: Props) => {
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
                title={'解锁此备份？'}
                onConfirmed={onLockToggle}
                onModalDismissed={() => setModal('')}
                buttonText={'是'}
            >
                您确定要解锁此备份吗？ 它将不再受到意外删除保护。
            </ConfirmationModal>
            <ConfirmationModal
                visible={modal === 'restore'}
                title={'从备份恢复？'}
                buttonText={'回档'}
                onConfirmed={() => doRestorationAction()}
                onModalDismissed={() => setModal('')}
            >
                <p css={tw`text-neutral-300`}>
                    该服务器将停止以恢复备份。 备份开始后，您将
                    无法控制服务器电源状态、访问文件管理器或创建其他备份
                    直到它完成。
                </p>
                <p css={tw`text-neutral-300 mt-4`}>
                    确定继续?
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
                        在恢复此备份之前删除所有文件和文件夹。
                    </label>
                </p>
            </ConfirmationModal>
            <ConfirmationModal
                visible={modal === 'delete'}
                title={'删除此备份?'}
                buttonText={'是'}
                onConfirmed={() => doDeletion()}
                onModalDismissed={() => setModal('')}
            >
                您确定要删除此备份吗？ 这是一个永久性操作。
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
                                <span css={tw`ml-2`}>下载</span>
                            </DropdownButtonRow>
                        </Can>
                        <Can action={'backup.restore'}>
                            <DropdownButtonRow onClick={() => setModal('restore')}>
                                <FontAwesomeIcon fixedWidth icon={faBoxOpen} css={tw`text-xs`}/>
                                <span css={tw`ml-2`}>恢复</span>
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
                                    {backup.isLocked ? '解锁' : '锁定'}
                                </DropdownButtonRow>
                                {!backup.isLocked &&
                                <DropdownButtonRow danger onClick={() => setModal('delete')}>
                                    <FontAwesomeIcon fixedWidth icon={faTrashAlt} css={tw`text-xs`}/>
                                    <span css={tw`ml-2`}>删除</span>
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
