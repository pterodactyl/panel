import ServerManageContainer from '@/components/admin/servers/ServerManageContainer';
import ServerStartupContainer from '@/components/admin/servers/ServerStartupContainer';
import React, { useEffect, useState } from 'react';
import { useLocation } from 'react-router';
import tw from 'twin.macro';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import { action, Action, Actions, createContextStore, useStoreActions } from 'easy-peasy';
import { Server } from '@/api/admin/servers/getServers';
import getServer from '@/api/admin/servers/getServer';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { ApplicationStore } from '@/state';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import ServerSettingsContainer from '@/components/admin/servers/ServerSettingsContainer';

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

const ServerRouter = () => {
    const location = useLocation();
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const [ loading, setLoading ] = useState(true);

    const server = Context.useStoreState(state => state.server);
    const setServer = Context.useStoreActions(actions => actions.setServer);

    useEffect(() => {
        clearFlashes('server');

        getServer(Number(match.params?.id), [ 'user' ])
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
            <div css={tw`w-full flex flex-row items-center mb-4`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{server.name}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{server.uuid}</p>
                </div>
            </div>

            <FlashMessageRender byKey={'server'} css={tw`mb-4`}/>

            <SubNavigation>
                <SubNavigationLink to={`${match.url}`} name={'Settings'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path clipRule="evenodd" fillRule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/startup`} name={'Startup'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/databases`} name={'Databases'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path clipRule="evenodd" fillRule="evenodd" d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z" />
                        <path clipRule="evenodd" fillRule="evenodd" d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z" />
                        <path clipRule="evenodd" fillRule="evenodd" d="M17 5c0 1.657-3.134 3-7 3S3 6.657 3 5s3.134-3 7-3 7 1.343 7 3z" />
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/mounts`} name={'Mounts'}>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                </SubNavigationLink>

                <SubNavigationLink to={`${match.url}/manage`} name={'Manage'}>
                    <svg xmlns="http://www.w3.org/2000/svg" className="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clipRule="evenodd" />
                    </svg>
                </SubNavigationLink>
            </SubNavigation>

            <Switch location={location}>
                <Route path={`${match.path}`} exact>
                    <ServerSettingsContainer/>
                </Route>
                <Route path={`${match.path}/startup`} exact>
                    <ServerStartupContainer/>
                </Route>
                <Route path={`${match.path}/manage`} exact>
                    <ServerManageContainer/>
                </Route>
            </Switch>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <ServerRouter/>
        </Context.Provider>
    );
};
