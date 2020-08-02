import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';
import Spinner from '@/components/elements/Spinner';
import getServerBackups from '@/api/server/backups/getServerBackups';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import { httpErrorToHuman } from '@/api/http';
import Can from '@/components/elements/Can';
import CreateBackupButton from '@/components/server/backups/CreateBackupButton';
import FlashMessageRender from '@/components/FlashMessageRender';
import BackupRow from '@/components/server/backups/BackupRow';
import { ServerContext } from '@/state/server';
import PageContentBlock from '@/components/elements/PageContentBlock';
import tw from 'twin.macro';

export default () => {
    const { uuid, featureLimits, name: serverName } = useServer();
    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const backups = ServerContext.useStoreState(state => state.backups.data);
    const setBackups = ServerContext.useStoreActions(actions => actions.backups.setBackups);

    useEffect(() => {
        clearFlashes('backups');
        getServerBackups(uuid)
            .then(data => setBackups(data.items))
            .catch(error => {
                console.error(error);
                addError({ key: 'backups', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, []);

    if (backups.length === 0 && loading) {
        return <Spinner size={'large'} centered/>;
    }

    return (
        <PageContentBlock>
            <Helmet>
                <title> {serverName} | Backups</title>
            </Helmet>
            <FlashMessageRender byKey={'backups'} css={tw`mb-4`}/>
            {!backups.length ?
                <p css={tw`text-center text-sm text-neutral-400`}>
                    There are no backups stored for this server.
                </p>
                :
                <div>
                    {backups.map((backup, index) => <BackupRow
                        key={backup.uuid}
                        backup={backup}
                        css={index > 0 ? tw`mt-2` : undefined}
                    />)}
                </div>
            }
            {featureLimits.backups === 0 &&
                <p css={tw`text-center text-sm text-neutral-400`}>
                    Backups cannot be created for this server.
                </p>
            }
            <Can action={'backup.create'}>
                {(featureLimits.backups > 0 && backups.length > 0) &&
                <p css={tw`text-center text-xs text-neutral-400 mt-2`}>
                    {backups.length} of {featureLimits.backups} backups have been created for this server.
                </p>
                }
                {featureLimits.backups > 0 && featureLimits.backups !== backups.length &&
                <div css={tw`mt-6 flex justify-end`}>
                    <CreateBackupButton/>
                </div>
                }
            </Can>
        </PageContentBlock>
    );
};
