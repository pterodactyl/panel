import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArchive, faEllipsisH, faLock } from '@fortawesome/free-solid-svg-icons';
import { format, formatDistanceToNow } from 'date-fns';
import Spinner from '@/components/elements/Spinner';
import { bytesToString } from '@/lib/formatters';
import Can from '@/components/elements/Can';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import BackupContextMenu from '@/components/server/backups/BackupContextMenu';
import GreyRowBox from '@/components/elements/GreyRowBox';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerBackup } from '@/api/server/types';
import classNames from 'classnames';

interface Props {
    backup: ServerBackup;
    className?: string;
}

export default ({ backup, className }: Props) => {
    const { mutate } = getServerBackups();

    useWebsocketEvent(`${SocketEvent.BACKUP_COMPLETED}:${backup.uuid}` as SocketEvent, (data) => {
        try {
            const parsed = JSON.parse(data);

            mutate(
                (data) => ({
                    ...data,
                    items: data.items.map((b) =>
                        b.uuid !== backup.uuid
                            ? b
                            : {
                                  ...b,
                                  isSuccessful: parsed.is_successful || true,
                                  checksum: (parsed.checksum_type || '') + ':' + (parsed.checksum || ''),
                                  bytes: parsed.file_size || 0,
                                  completedAt: new Date(),
                              }
                    ),
                }),
                false
            );
        } catch (e) {
            console.warn(e);
        }
    });

    return (
        <GreyRowBox className={classNames('flex-wrap md:flex-nowrap items-center', className)}>
            <div className="flex items-center truncate w-full md:flex-1">
                <div className="mr-4">
                    {backup.completedAt !== null ? (
                        backup.isLocked ? (
                            <FontAwesomeIcon icon={faLock} className="text-yellow-500" />
                        ) : (
                            <FontAwesomeIcon icon={faArchive} className="text-neutral-300" />
                        )
                    ) : (
                        <Spinner size={'small'} />
                    )}
                </div>
                <div className="flex flex-col truncate">
                    <div className="flex items-center text-sm mb-1">
                        {backup.completedAt !== null && !backup.isSuccessful && (
                            <span
                                className="bg-red-500 py-px px-2 rounded-full text-white text-xs uppercase border border-red-600 mr-2"
                            >
                                Failed
                            </span>
                        )}
                        <p className="break-words truncate">{backup.name}</p>
                        {backup.completedAt !== null && backup.isSuccessful && (
                            <span className="ml-3 text-neutral-300 text-xs font-extralight hidden sm:inline">
                                {bytesToString(backup.bytes)}
                            </span>
                        )}
                    </div>
                    <p className="mt-1 md:mt-0 text-xs text-neutral-400 font-mono truncate">{backup.checksum}</p>
                </div>
            </div>
            <div className="flex-1 md:flex-none md:w-48 mt-4 md:mt-0 md:ml-8 md:text-center">
                <p title={format(backup.createdAt, 'ddd, MMMM do, yyyy HH:mm:ss')} className="text-sm">
                    {formatDistanceToNow(backup.createdAt, { includeSeconds: true, addSuffix: true })}
                </p>
                <p className="text-2xs text-neutral-500 uppercase mt-1">Created</p>
            </div>
            <Can action={['backup.download', 'backup.restore', 'backup.delete']} matchAny>
                <div className="mt-4 md:mt-0 ml-6" style={{ marginRight: '-0.5rem' }}>
                    {!backup.completedAt ? (
                        <div className="p-2 invisible">
                            <FontAwesomeIcon icon={faEllipsisH} />
                        </div>
                    ) : (
                        <BackupContextMenu backup={backup} />
                    )}
                </div>
            </Can>
        </GreyRowBox>
    );
};
