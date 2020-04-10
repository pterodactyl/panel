import React, { useEffect, useState } from 'react';
import getServerDatabases from '@/api/server/getServerDatabases';
import { ServerContext } from '@/state/server';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import DatabaseRow from '@/components/server/databases/DatabaseRow';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';
import CreateDatabaseButton from '@/components/server/databases/CreateDatabaseButton';
import Can from '@/components/elements/Can';
import useFlash from '@/plugins/useFlash';
import useServer from '@/plugins/useServer';
import ListRefreshIndicator from '@/components/elements/ListRefreshIndicator';

export default () => {
    const { uuid, featureLimits } = useServer();
    const { addError, clearFlashes } = useFlash();
    const [ loading, setLoading ] = useState(true);

    const databases = ServerContext.useStoreState(state => state.databases.data);
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
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'databases'} className={'mb-4'}/>
            {(!databases.length && loading) ?
                <Spinner size={'large'} centered={true}/>
                :
                <CSSTransition classNames={'fade'} timeout={250}>
                    <>
                        <ListRefreshIndicator visible={loading}/>
                        {databases.length > 0 ?
                            databases.map((database, index) => (
                                <DatabaseRow
                                    key={database.id}
                                    database={database}
                                    className={index > 0 ? 'mt-1' : undefined}
                                />
                            ))
                            :
                            <p className={'text-center text-sm text-neutral-400'}>
                                {featureLimits.databases > 0 ?
                                    `It looks like you have no databases.`
                                    :
                                    `Databases cannot be created for this server.`
                                }
                            </p>
                        }
                        <Can action={'database.create'}>
                            {featureLimits.databases > 0 &&
                            <div className={'mt-6 flex justify-end'}>
                                <CreateDatabaseButton/>
                            </div>
                            }
                        </Can>
                    </>
                </CSSTransition>
            }
        </div>
    );
};
