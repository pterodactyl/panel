import React, { useEffect, useState } from 'react';
import getServerDatabases from '@/api/server/databases/getServerDatabases';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import DatabaseRow from '@/components/server/databases/DatabaseRow';
import Spinner from '@/components/elements/Spinner';
import CreateDatabaseButton from '@/components/server/databases/CreateDatabaseButton';
import Can from '@/components/elements/Can';
import useFlash from '@/plugins/useFlash';
import tw from 'twin.macro';
import Fade from '@/components/elements/Fade';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import { useDeepMemoize } from '@/plugins/useDeepMemoize';

export default () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const databaseLimit = ServerContext.useStoreState(state => state.server.data!.featureLimits.databases);

    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const databases = useDeepMemoize(ServerContext.useStoreState(state => state.databases.data));
    const setDatabases = ServerContext.useStoreActions(state => state.databases.setDatabases);

    useEffect(() => {
        setLoading(!databases.length);
        clearFlashes('databases');

        getServerDatabases(uuid)
            .then(databases => setDatabases(databases))
            .catch(error => {
                console.error(error);
                addError({ key: 'databases', message: httpErrorToHuman(error) });
            })
            .then(() => setLoading(false));
    }, []);

    return (
        <ServerContentBlock title={'数据库'}>
            <FlashMessageRender byKey={'databases'} css={tw`mb-4`}/>
            {(!databases.length && loading) ?
                <Spinner size={'large'} centered/>
                :
                <Fade timeout={150}>
                    <>
                        {databases.length > 0 ?
                            databases.map((database, index) => (
                                <DatabaseRow
                                    key={database.id}
                                    database={database}
                                    className={index > 0 ? 'mt-1' : undefined}
                                />
                            ))
                            :
                            <p css={tw`text-center text-sm text-neutral-300`}>
                                {databaseLimit > 0 ?
                                    '看起来此服务器实例没有数据库.'
                                    :
                                    '此服务器实例无法创建数据库'
                                }
                            </p>
                        }
                        <Can action={'database.create'}>
                            <div css={tw`mt-6 flex items-center justify-end`}>
                                {(databaseLimit > 0 && databases.length > 0) &&
                                <p css={tw`text-sm text-neutral-300 mb-4 sm:mr-6 sm:mb-0`}>
                                    {databases.length} / {databaseLimit} 个数据库已为此服务器实例创建
                                </p>
                                }
                                {databaseLimit > 0 && databaseLimit !== databases.length &&
                                    <CreateDatabaseButton css={tw`flex justify-end mt-6`}/>
                                }
                            </div>
                        </Can>
                    </>
                </Fade>
            }
        </ServerContentBlock>
    );
};
