import React from 'react';
import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArchive } from '@fortawesome/free-solid-svg-icons/faArchive';
import format from 'date-fns/format';
import distanceInWordsToNow from 'date-fns/distance_in_words_to_now'
import Spinner from '@/components/elements/Spinner';
import { faCloudDownloadAlt } from '@fortawesome/free-solid-svg-icons/faCloudDownloadAlt';

interface Props {
    backup: ServerBackup;
    className?: string;
}

export default ({ backup, className }: Props) => {
    return (
        <div className={`grey-row-box flex items-center ${className}`}>
            <div className={'mr-4'}>
                <FontAwesomeIcon icon={faArchive} className={'text-neutral-300'}/>
            </div>
            <div className={'flex-1'}>
                <p className={'text-sm mb-1'}>{backup.name}</p>
                <p className={'text-xs text-neutral-400 font-mono'}>{backup.uuid}</p>
            </div>
            <div className={'ml-4 text-center'}>
                <p
                    title={format(backup.createdAt, 'ddd, MMMM Do, YYYY HH:mm:ss Z')}
                    className={'text-sm'}
                >
                    {distanceInWordsToNow(backup.createdAt, { includeSeconds: true, addSuffix: true })}
                </p>
                <p className={'text-2xs text-neutral-500 uppercase mt-1'}>Created</p>
            </div>
            <div className={'ml-6'} style={{ marginRight: '-0.5rem' }}>
                {!backup.completedAt ?
                    <div title={'Backup is in progress'} className={'p-2'}>
                        <Spinner size={'tiny'}/>
                    </div>
                    :
                    <a href={'#'} className={'text-sm text-neutral-300 p-2 transition-colors duration-250 hover:text-cyan-400'}>
                        <FontAwesomeIcon icon={faCloudDownloadAlt}/>
                    </a>
                }
            </div>
        </div>
    );
};
