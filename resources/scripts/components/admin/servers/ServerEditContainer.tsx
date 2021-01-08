import React, { useEffect, useState } from 'react';
import tw from 'twin.macro';
import { useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Server } from '@/api/admin/servers/getServers';
import getServer from '@/api/admin/servers/getServer';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';

interface ctx {
    server: Server | undefined;
    setServer: Action<ctx, Server | undefined>;
}

export const Context = createContextStore<ctx>({
    server: undefined,

    setServer: action((state, payload) => {
        state.server = payload;
    }),
});

const ServerEditContainer = () => {
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const server = Context.useStoreState(state => state.server);
    const setServer = Context.useStoreActions(actions => actions.setServer);

    useEffect(() => {
        clearFlashes('server');

        getServer(Number(match.params?.id), [])
            .then(server => setServer(server))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'server', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || server === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'server'} css={tw`mb-4`}/>

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'}/>
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Server - ' + server.name}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col`}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{server.name}</h2>
                    {
                        (server.description || '').length < 1 ?
                            <p css={tw`text-base text-neutral-400`}>
                                <span css={tw`italic`}>No description</span>
                            </p>
                            :
                            <p css={tw`text-base text-neutral-400`}>{server.description}</p>
                    }
                </div>
            </div>

            <FlashMessageRender byKey={'server'} css={tw`mb-4`}/>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <ServerEditContainer/>
        </Context.Provider>
    );
};
