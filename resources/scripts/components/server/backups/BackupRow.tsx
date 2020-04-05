import React, { useState } from 'react';
import { ServerBackup } from '@/api/server/backups/getServerBackups';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArchive } from '@fortawesome/free-solid-svg-icons/faArchive';
import format from 'date-fns/format';
import distanceInWordsToNow from 'date-fns/distance_in_words_to_now';
import Spinner from '@/components/elements/Spinner';
import { faCloudDownloadAlt } from '@fortawesome/free-solid-svg-icons/faCloudDownloadAlt';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { bytesToHuman } from '@/helpers';
import Can from '@/components/elements/Can';
import { join } from "path";
import useServer from '@/plugins/useServer';

interface Props {
    backup: ServerBackup;
    className?: string;
}

const DownloadModal = ({ checksum, ...props }: RequiredModalProps & { checksum: string }) => (
    <Modal {...props}>
        <h3 className={'mb-6'}>Verify file checksum</h3>
        <p className={'text-sm'}>
            The SHA256 checksum of this file is:
        </p>
        <pre className={'mt-2 text-sm p-2 bg-neutral-900 rounded'}>
            <code className={'block font-mono'}>{checksum}</code>
        </pre>
    </Modal>
);

export default ({ backup, className }: Props) => {
    const { uuid } = useServer();
    const [ visible, setVisible ] = useState(false);

    return (
        <div className={`grey-row-box flex items-center ${className}`}>
            {visible &&
            <DownloadModal
                visible={visible}
                appear={true}
                onDismissed={() => setVisible(false)}
                checksum={backup.sha256Hash}
            />
            }
            <div className={'mr-4'}>
                {backup.completedAt ?
                    <FontAwesomeIcon icon={faArchive} className={'text-neutral-300'}/>
                    :
                    <Spinner size={'tiny'}/>
                }
            </div>
            <div className={'flex-1'}>
                <p className={'text-sm mb-1'}>
                    {backup.name}
                    {backup.completedAt &&
                    <span className={'ml-3 text-neutral-300 text-xs font-thin'}>{bytesToHuman(backup.bytes)}</span>
                    }
                </p>
                <p className={'text-xs text-neutral-400 font-mono'}>
                    {backup.uuid}
                </p>
            </div>
            <div className={'ml-8 text-center'}>
                <p
                    title={format(backup.createdAt, 'ddd, MMMM Do, YYYY HH:mm:ss Z')}
                    className={'text-sm'}
                >
                    {distanceInWordsToNow(backup.createdAt, { includeSeconds: true, addSuffix: true })}
                </p>
                <p className={'text-2xs text-neutral-500 uppercase mt-1'}>Created</p>
            </div>
            <Can action={'backup.download'}>
                <div className={'ml-6'} style={{ marginRight: '-0.5rem' }}>
                    {!backup.completedAt ?
                        <div className={'p-2 invisible'}>
                            <FontAwesomeIcon icon={faCloudDownloadAlt}/>
                        </div>
                        :
                        <a
                            href={`/api/client/servers/${uuid}/backups/${backup.uuid}/download`}
                            target={'_blank'}
                            onClick={() => {
                                setVisible(true);
                            }}
                            className={'text-sm text-neutral-300 p-2 transition-colors duration-250 hover:text-cyan-400'}
                        >
                            <FontAwesomeIcon icon={faCloudDownloadAlt}/>
                        </a>
                    }
                </div>
            </Can>
        </div>
    );
};
