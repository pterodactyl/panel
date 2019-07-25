import React, { useEffect, useState } from 'react';
import getServerDatabases, { ServerDatabase } from '@/api/server/getServerDatabases';
import { ServerContext } from '@/state/server';
import { Actions, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import FlashMessageRender from '@/components/FlashMessageRender';
import DatabaseRow from '@/components/server/databases/DatabaseRow';
import Spinner from '@/components/elements/Spinner';
import { CSSTransition } from 'react-transition-group';
import CreateDatabaseButton from '@/components/server/databases/CreateDatabaseButton';

export default () => {
    const [ loading, setLoading ] = useState(true);
    const [ databases, setDatabases ] = useState<ServerDatabase[]>([]);
    const server = ServerContext.useStoreState(state => state.server.data!);
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);

    useEffect(() => {
        clearFlashes('databases');
        getServerDatabases(server.uuid)
            .then(databases => {
                setDatabases(databases);
                setLoading(false);
            })
            .catch(error => addFlash({
                key: 'databases',
                title: 'Error',
                message: httpErrorToHuman(error),
                type: 'error',
            }));
    }, []);

    return (
        <div className={'my-10 mb-6'}>
            <FlashMessageRender byKey={'databases'}/>
            {loading ?
                <Spinner large={true} centered={true}/>
                :
                <CSSTransition classNames={'fade'} timeout={250}>
                    <React.Fragment>
                        {databases.length > 0 ?
                            databases.map((database, index) => (
                                <DatabaseRow
                                    key={database.id}
                                    database={database}
                                    onDelete={() => setDatabases(s => [ ...s.filter(d => d.id !== database.id) ])}
                                    className={index > 0 ? 'mt-1' : undefined}
                                />
                            ))
                            :
                            <p className={'text-center text-sm text-neutral-200'}>
                                It looks like you have no databases. Click the button below to create one now.
                            </p>
                        }
                        <div className={'mt-6 flex justify-end'}>
                            <CreateDatabaseButton onCreated={database => setDatabases(s => [ ...s, database ])}/>
                        </div>
                    </React.Fragment>
                </CSSTransition>
            }
        </div>
    );
};
