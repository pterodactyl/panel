import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Database } from '@/api/admin/databases/getDatabases';
import getDatabase from '@/api/admin/databases/getDatabase';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

interface ctx {
    database: Database | undefined;
    setDatabase: Action<ctx, Database | undefined>;
}

export const Context = createContextStore<ctx>({
    database: undefined,

    setDatabase: action((state, payload) => {
        state.database = payload;
    }),
});

const DatabaseEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const database = Context.useStoreState(state => state.database);
    const setDatabase = Context.useStoreActions(actions => actions.setDatabase);

    useEffect(() => {
        clearFlashes('database');

        getDatabase(Number(match.params?.id))
            .then(database => setDatabase(database))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'database', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || database === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'database'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Database - ' + database.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{database.name}</h2>
                    <p css={tw`text-base text-neutral-400`}>{database.getAddress()}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'database'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <DatabaseEditContainer/>
        </Context.Provider>
    );
};
