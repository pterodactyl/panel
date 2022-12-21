import TransferListener from '@/components/server/TransferListener';
import { Fragment, useEffect, useState } from 'react';
import { NavLink, Route, Routes, useParams } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import Can from '@/components/elements/Can';
import Spinner from '@/components/elements/Spinner';
import { NotFound, ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from 'easy-peasy';
import SubNavigation from '@/components/elements/SubNavigation';
import InstallListener from '@/components/server/InstallListener';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons';
import { useLocation } from 'react-router';
import ConflictStateRenderer from '@/components/server/ConflictStateRenderer';
import PermissionRoute from '@/components/elements/PermissionRoute';
import routes from '@/routers/routes';

function ServerRouter() {
    const params = useParams<'id'>();
    const location = useLocation();

    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const [error, setError] = useState('');

    const id = ServerContext.useStoreState(state => state.server.data?.id);
    const uuid = ServerContext.useStoreState(state => state.server.data?.uuid);
    const inConflictState = ServerContext.useStoreState(state => state.server.inConflictState);
    const serverId = ServerContext.useStoreState(state => state.server.data?.internalId);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);

    useEffect(
        () => () => {
            clearServerState();
        },
        [],
    );

    useEffect(() => {
        setError('');

        if (params.id === undefined) {
            return;
        }

        getServer(params.id).catch(error => {
            console.error(error);
            setError(httpErrorToHuman(error));
        });

        return () => {
            clearServerState();
        };
    }, [params.id]);

    return (
        <Fragment key={'server-router'}>
            <NavigationBar />
            {!uuid || !id ? (
                error ? (
                    <ServerError message={error} />
                ) : (
                    <Spinner size="large" centered />
                )
            ) : (
                <>
                    <SubNavigation>
                        <div>
                            {routes.server
                                .filter(route => route.path !== undefined)
                                .map(route =>
                                    route.permission ? (
                                        <Can key={route.path} action={route.permission} matchAny>
                                            <NavLink to={`/server/${id}/${route.path ?? ''}`.replace(/\/$/, '')} end>
                                                {route.name}
                                            </NavLink>
                                        </Can>
                                    ) : (
                                        <NavLink
                                            key={route.path}
                                            to={`/server/${id}/${route.path ?? ''}`.replace(/\/$/, '')}
                                            end
                                        >
                                            {route.name}
                                        </NavLink>
                                    ),
                                )}
                            {rootAdmin && (
                                <NavLink to={`/admin/servers/${serverId}`}>
                                    <FontAwesomeIcon icon={faExternalLinkAlt} />
                                </NavLink>
                            )}
                        </div>
                    </SubNavigation>
                    <InstallListener />
                    <TransferListener />
                    <WebsocketHandler />
                    {inConflictState && (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${id}/`))) ? (
                        <ConflictStateRenderer />
                    ) : (
                        <ErrorBoundary>
                            <Routes location={location}>
                                {routes.server.map(({ route, permission, component: Component }) => (
                                    <Route
                                        key={route}
                                        path={route}
                                        element={
                                            <PermissionRoute permission={permission}>
                                                <Spinner.Suspense>
                                                    <Component />
                                                </Spinner.Suspense>
                                            </PermissionRoute>
                                        }
                                    />
                                ))}

                                <Route path="*" element={<NotFound />} />
                            </Routes>
                        </ErrorBoundary>
                    )}
                </>
            )}
        </Fragment>
    );
}

export default ServerRouter;
