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
import useServer from '@/plugins/useServer';
import getBackupDownloadUrl from '@/api/server/backups/getBackupDownloadUrl';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';

interface Props {
    backup: ServerBackup;
    onBackupUpdated: (backup: ServerBackup) => void;
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

export default ({ backup, onBackupUpdated, className }: Props) => {
    const { uuid } = useServer();
    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(false);
    const [ visible, setVisible ] = useState(false);

    useWebsocketEvent(`backup completed:${backup.uuid}`, data => {
        try {
            const parsed = JSON.parse(data);
            onBackupUpdated({
                ...backup,
                sha256Hash: parsed.sha256_hash || '',
                bytes: parsed.file_size || 0,
                completedAt: new Date(),
            });
        } catch (e) {
            console.warn(e);
        }
    });

    const getBackupLink = () => {
        setLoading(true);
        clearFlashes('backups');
        getBackupDownloadUrl(uuid, backup.uuid)
            .then(url => {
                // @ts-ignore
                window.location = url;
                setVisible(true);
            })
            .catch(error => {
                console.error(error);
                addError({ key: 'backups', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    };

    return (
        <div className={`grey-row-box flex items-center ${className}`}>
            <SpinnerOverlay visible={loading} fixed={true}/>
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
                        <button
                            onClick={() => getBackupLink()}
                            className={'text-sm text-neutral-300 p-2 transition-colors duration-250 hover:text-cyan-400'}
                        >
                            <FontAwesomeIcon icon={faCloudDownloadAlt}/>
                        </button>
                    }
                </div>
            </Can>
        </div>
    );
};
