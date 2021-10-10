import ServerManageContainer from '@/components/admin/servers/ServerManageContainer';
import ServerStartupContainer from '@/components/admin/servers/ServerStartupContainer';
import React, { useEffect } from 'react';
import { useLocation } from 'react-router';
import tw from 'twin.macro';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import Spinner from '@/components/elements/Spinner';
import FlashMessageRender from '@/components/FlashMessageRender';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import ServerSettingsContainer from '@/components/admin/servers/ServerSettingsContainer';
import useFlash from '@/plugins/useFlash';
import { useServerFromRoute } from '@/api/admin/server';
import { AdjustmentsIcon, CogIcon, DatabaseIcon, FolderIcon, ShieldExclamationIcon } from '@heroicons/react/outline';

export default () => {
    const location = useLocation();
    const match = useRouteMatch<{ id?: string }>();

    const { clearFlashes, clearAndAddHttpError } = useFlash();
    const { data: server, error, isValidating, mutate } = useServerFromRoute();

    useEffect(() => {
        mutate();
    }, []);

    useEffect(() => {
        if (!error) clearFlashes('server');
        if (error) clearAndAddHttpError({ error, key: 'server' });
    }, [ error ]);

    if (!server || (error && isValidating)) {
        return (
            <AdminContentBlock showFlashKey={'server'}>
                <Spinner size={'large'} centered/>;
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Server - ' + server.name}>
            <FlashMessageRender byKey={'backups'} css={tw`mb-4`}/>
            <div css={tw`w-full flex flex-row items-center mb-4`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>{server.name}</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>{server.uuid}</p>
                </div>
            </div>
            <FlashMessageRender byKey={'server'} css={tw`mb-4`}/>
            <SubNavigation>
                <SubNavigationLink to={`${match.url}`} name={'Settings'} icon={CogIcon}/>
                <SubNavigationLink to={`${match.url}/startup`} name={'Startup'} icon={AdjustmentsIcon}/>
                <SubNavigationLink to={`${match.url}/databases`} name={'Databases'} icon={DatabaseIcon}/>
                <SubNavigationLink to={`${match.url}/mounts`} name={'Mounts'} icon={FolderIcon}/>
                <SubNavigationLink to={`${match.url}/manage`} name={'Manage'} icon={ShieldExclamationIcon}/>
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
