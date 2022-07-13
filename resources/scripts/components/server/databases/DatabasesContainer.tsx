import tw from 'twin.macro';
import useFlash from '@/plugins/useFlash';
import Can from '@/components/elements/Can';
import { httpErrorToHuman } from '@/api/http';
import Fade from '@/components/elements/Fade';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import Spinner from '@/components/elements/Spinner';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';
import FlashMessageRender from '@/components/FlashMessageRender';
import DatabaseRow from '@/components/server/databases/DatabaseRow';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import getServerDatabases from '@/api/server/databases/getServerDatabases';
import CreateDatabaseButton from '@/components/server/databases/CreateDatabaseButton';

export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const databaseLimit = ServerContext.useStoreState((state) => state.server.data!.featureLimits.databases);

    const { addError, clearFlashes } = useFlash();
    const [loading, setLoading] = useState(true);

    const databases = useDeepMemoize(ServerContext.useStoreState((state) => state.databases.data));
    const setDatabases = ServerContext.useStoreActions((state) => state.databases.setDatabases);

    useEffect(() => {
        setLoading(!databases.length);
        clearFlashes('databases');

        getServerDatabases(uuid)
            .then((databases) => setDatabases(databases))
            .catch((error) => {
                console.error(error);
                addError({ key: 'databases', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <ServerContentBlock title={'Databases'}>
            <FlashMessageRender byKey={'databases'} css={tw`mb-4`} />
            <h1 className={'j-left text-5xl'}>Databases</h1>
            <h3 className={'j-left text-2xl mt-2 text-neutral-500 mb-10'}>Create databases for your application.</h3>
            {!databases.length && loading ? (
                <Spinner size={'large'} centered />
            ) : (
                <Fade timeout={150}>
                    <>
                        {databases.length > 0 ? (
                            databases.map((database, index) => (
                                <DatabaseRow
                                    key={database.id}
                                    database={database}
                                    className={index > 0 ? 'mt-1' : undefined}
                                />
                            ))
                        ) : (
                            <p css={tw`text-center text-sm text-neutral-300`}>
                                {databaseLimit > 0
                                    ? 'It looks like you have no databases.'
                                    : 'Databases cannot be created for this server.'}
                            </p>
                        )}
                        <Can action={'database.create'}>
                            <div css={tw`mt-6 flex items-center justify-end`}>
                                {databaseLimit > 0 && databases.length > 0 && (
                                    <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                                        {databases.length} of {databaseLimit} databases have been allocated to this
                                        server.
                                    </p>
                                )}
                                {databaseLimit > 0 && databaseLimit !== databases.length && (
                                    <CreateDatabaseButton css={tw`flex justify-end mt-6`} />
                                )}
                            </div>
                        </Can>
                    </>
                </Fade>
            )}
        </ServerContentBlock>
    );
};
