import React from 'react';
import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArchive, faEllipsisH } from '@fortawesome/free-solid-svg-icons';
import { format, formatDistanceToNow } from 'date-fns';
import Spinner from '@/components/elements/Spinner';
import { bytesToHuman } from '@/helpers';
import Can from '@/components/elements/Can';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { ServerContext } from '@/state/server';
import BackupContextMenu from '@/components/server/backups/BackupContextMenu';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';

interface Props {
    backup: ServerBackup;
    className?: string;
}

export default ({ backup, className }: Props) => {
    const appendBackup = ServerContext.useStoreActions(actions => actions.backups.appendBackup);

    useWebsocketEvent(`backup completed:${backup.uuid}`, data => {
        try {
            const parsed = JSON.parse(data);
            appendBackup({
                ...backup,
                sha256Hash: parsed.sha256_hash || '',
                bytes: parsed.file_size || 0,
                completedAt: new Date(),
            });
        } catch (e) {
            console.warn(e);
        }
    });

    return (
        <GreyRowBox css={tw`flex items-center`} className={className}>
            <div css={tw`mr-4`}>
                {backup.completedAt ?
                    <FontAwesomeIcon icon={faArchive} css={tw`text-neutral-300`}/>
                    :
                    <Spinner size={'small'}/>
                }
            </div>
            <div css={tw`flex-1`}>
                <p css={tw`text-sm mb-1`}>
                    {backup.name}
                    {backup.completedAt &&
                    <span css={tw`ml-3 text-neutral-300 text-xs font-thin`}>{bytesToHuman(backup.bytes)}</span>
                    }
                </p>
                <p css={tw`text-xs text-neutral-400 font-mono`}>
                    {backup.uuid}
                </p>
            </div>
            <div css={tw`ml-8 text-center`}>
                <p
                    title={format(backup.createdAt, 'ddd, MMMM do, yyyy HH:mm:ss')}
                    css={tw`text-sm`}
                >
                    {formatDistanceToNow(backup.createdAt, { includeSeconds: true, addSuffix: true })}
                </p>
                <p css={tw`text-2xs text-neutral-500 uppercase mt-1`}>Created</p>
            </div>
            <Can action={'backup.download'}>
                <div css={tw`ml-6`} style={{ marginRight: '-0.5rem' }}>
                    {!backup.completedAt ?
                        <div css={tw`p-2 invisible`}>
                            <FontAwesomeIcon icon={faEllipsisH}/>
                        </div>
                        :
                        <BackupContextMenu backup={backup}/>
                    }
                </div>
            </Can>
        </GreyRowBox>
    );
};
