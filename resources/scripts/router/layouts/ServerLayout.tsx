import React, { useEffect } from 'react';
import { Link, Outlet, useLocation, useParams } from '@tanstack/react-router';
import { ExternalLink } from 'lucide-react';
import { useServerQuery } from '@/api/server/queries';
import { useServerStore } from '@/state/server';
import { ServerContext } from '@/state/server/context';
import { httpErrorToHuman } from '@/api/http';
import SubNavigation from '@/components/elements/SubNavigation';
import SubNavigationLayout from '@/components/elements/SubNavigationLayout';
import NavLink from '@/router/NavLink';
import Can from '@/components/elements/Can';
import Icon from '@/components/elements/Icon';
import Spinner from '@/components/elements/Spinner';
import { ServerError } from '@/components/elements/ScreenBlock';
import InstallListener from '@/components/server/InstallListener';
import TransferListener from '@/components/server/TransferListener';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import ConflictStateRenderer from '@/components/server/ConflictStateRenderer';
import { getAreaNav } from '@/router/nav';
import { useIsRenderedRoute } from '@/router/renderedRoute';
import { useCurrentUser } from '@/api/account/queries';
import Slot from '@/extensions/Slot';
import NavigationLabel from '@/extensions/NavigationLabel';
import { ScreenGate } from '@/extensions/screenNavigation';

const ServerConsoleContainer = React.lazy(() => import('@/components/server/console/ServerConsoleContainer'));

function ServerLayoutInner() {
    const { id } = useParams({ from: '/authenticated/server/$id' });
    const location = useLocation();

    const rootAdmin = useCurrentUser().rootAdmin;
    const { data, error } = useServerQuery(id);

    const server = data;
    const inConflictState =
        !!server &&
        (server.attributes.status !== null ||
            server.attributes.is_transferring ||
            server.attributes.is_node_under_maintenance);
    const clearServerState = useServerStore((state) => state.clearServerState);

    const to = (segment: string) => `/server/${id}${segment ? `/${segment}` : ''}`;

    // The console stays mounted and is shown or hidden here, while every other page comes
    // from <Outlet />. Both have to switch in the same render: following the location
    // showed the console above the page being left, and hid it before the next one arrived.
    const isConsole = useIsRenderedRoute(to(''));

    useEffect(() => () => clearServerState(), [clearServerState]);

    if (!server) {
        return error ? <ServerError message={httpErrorToHuman(error)} /> : <Spinner size={'large'} centered />;
    }

    return (
        <SubNavigationLayout
            navigation={
                <SubNavigation>
                    <div>
                        <Slot name={'server.navigation.before'} data={server} />
                        {getAreaNav('server').map(({ segment, label, exact, permission, ...meta }) => (
                            <ScreenGate key={segment || '/'} screen={meta.screen}>
                                {permission ? (
                                    <Can action={permission} matchAny>
                                        <NavLink to={to(segment)} exact={exact} hash={segment === 'files' ? '/' : ''}>
                                            <NavigationLabel label={label} {...meta} />
                                        </NavLink>
                                    </Can>
                                ) : (
                                    <NavLink to={to(segment)} exact={exact} hash={segment === 'files' ? '/' : ''}>
                                        <NavigationLabel label={label} {...meta} />
                                    </NavLink>
                                )}
                            </ScreenGate>
                        ))}
                        {rootAdmin && (
                            <Link
                                to={'/panel/servers/$id'}
                                params={{ id: server.attributes.internal_id }}
                                aria-label={'Open server administration'}
                                title={'Open server administration'}
                            >
                                <Icon icon={ExternalLink} />
                            </Link>
                        )}
                        <Slot name={'server.navigation.after'} data={server} />
                    </div>
                </SubNavigation>
            }
        >
            <InstallListener />
            <TransferListener />
            <WebsocketHandler />
            {inConflictState &&
            (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${server.attributes.identifier}`))) ? (
                <ConflictStateRenderer />
            ) : (
                <>
                    {/* Stays mounted while hidden so the terminal survives tab switches. */}
                    <div className={isConsole ? undefined : 'hidden'}>
                        <Spinner.Suspense>
                            <ServerConsoleContainer active={isConsole} />
                        </Spinner.Suspense>
                    </div>
                    <Outlet />
                </>
            )}
        </SubNavigationLayout>
    );
}

export default function ServerLayout() {
    const { id } = useParams({ from: '/authenticated/server/$id' });

    return (
        <ServerContext.Provider key={id}>
            <ServerLayoutInner />
        </ServerContext.Provider>
    );
}
