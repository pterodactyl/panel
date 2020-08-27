import React, { useEffect } from 'react';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import CreateBackupButton from '@/components/server/backups/CreateBackupButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import BackupRow from '@/components/server/backups/BackupRow';
import tw from 'twin.macro';
import getServerBackups from '@/api/swr/getServerBackups';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';

export default () => {
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: backups, error, isValidating } = getServerBackups();

    const backupLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.backups);

    useEffect(() => {
        if (!error) {
            clearFlashes('backups');

            return;
        }

        clearAndAddHttpError({ error, key: 'backups' });
    }, [ error ]);

    if (!backups || (error && isValidating)) {
        return <Spinner size={'large'} centered/>;
    }

    return (
        <ServerContentBlock title={'Backups'}>
            <FlashMessageRender byKey={'backups'} css={tw`mb-4`}/>
            {!backups.items.length ?
                <p css={tw`text-center text-sm text-neutral-400`}>
                    There are no backups stored for this server.
                </p>
                :
                <div>
                    {backups.items.map((backup, index) => <BackupRow
                        key={backup.uuid}
                        backup={backup}
                        css={index > 0 ? tw`mt-2` : undefined}
                    />)}
                </div>
            }
            {backupLimit === 0 &&
            <p css={tw`text-center text-sm text-neutral-400`}>
                Backups cannot be created for this server.
            </p>
            }
            <Can action={'backup.create'}>
                {(backupLimit > 0 && backups.items.length > 0) &&
                <p css={tw`text-center text-xs text-neutral-400 mt-2`}>
                    {backups.items.length} of {backupLimit} backups have been created for this server.
                </p>
                }
                {backupLimit > 0 && backupLimit !== backups.items.length &&
                <div css={tw`mt-6 flex justify-end`}>
                    <CreateBackupButton/>
                </div>
                }
            </Can>
        </ServerContentBlock>
    );
};
