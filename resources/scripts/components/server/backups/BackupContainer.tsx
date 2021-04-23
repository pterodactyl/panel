import React, { useContext, useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import CreateBackupButton from '@/components/server/backups/CreateBackupButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import BackupRow from '@/components/server/backups/BackupRow';
import tw from 'twin.macro';
import getServerBackups, { Context as ServerBackupContext } from '@/api/swr/getServerBackups';
import { ServerContext } from '@/state/server';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import Pagination from '@/components/elements/Pagination';
import { useTranslation } from 'react-i18next';

const BackupContainer = () => {
    const { page, setPage } = useContext(ServerBackupContext);
    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: backups, error, isValidating } = getServerBackups();
    const { t } = useTranslation('server');

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
        <ServerContentBlock title={t('backups')}>
            <FlashMessageRender byKey={'backups'} css={tw`mb-4`}/>
            <Pagination data={backups} onPageSelect={setPage}>
                {({ items }) => (
                    !items.length ?
                        // Don't show any error messages if the server has no backups and the user cannot
                        // create additional ones for the server.
                        !backupLimit ?
                            null
                            :
                            <p css={tw`text-center text-sm text-neutral-300`}>
                                {page > 1 ?
                                    t('run_out_of_backups_for_pagination')
                                    :
                                    t('no_backups_exist')
                                }
                            </p>
                        :
                        items.map((backup, index) => <BackupRow
                            key={backup.uuid}
                            backup={backup}
                            css={index > 0 ? tw`mt-2` : undefined}
                        />)
                )}
            </Pagination>
            {backupLimit === 0 &&
            <p css={tw`text-center text-sm text-neutral-300`}>
                {t('backups_cant_be_created')}
            </p>
            }
            <Can action={'backup.create'}>
                <div css={tw`mt-6 sm:flex items-center justify-end`}>
                    {(backupLimit > 0 && backups.items.length > 0) &&
                    <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                        {t('used_backups_count', { used: backups.items.length, limit: backupLimit })}
                    </p>
                    }
                    {backupLimit > 0 && backupLimit !== backups.items.length &&
                    <CreateBackupButton css={tw`w-full sm:w-auto`}/>
                    }
                </div>
            </Can>
        </ServerContentBlock>
    );
};

export default () => {
    const [ page, setPage ] = useState<number>(1);
    return (
        <ServerBackupContext.Provider value={{ page, setPage }}>
            <BackupContainer/>
        </ServerBackupContext.Provider>
    );
};
