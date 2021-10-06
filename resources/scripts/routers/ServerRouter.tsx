import TransferListener from '@/components/server/TransferListener';
import React, { useEffect, useState } from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import DatabasesContainer from '@/components/server/databases/DatabasesContainer';
import FileManagerContainer from '@/components/server/files/FileManagerContainer';
import { CSSTransition } from 'react-transition-group';
import FileEditContainer from '@/components/server/files/FileEditContainer';
import SettingsContainer from '@/components/server/settings/SettingsContainer';
import ScheduleContainer from '@/components/server/schedules/ScheduleContainer';
import ScheduleEditContainer from '@/components/server/schedules/ScheduleEditContainer';
import UsersContainer from '@/components/server/users/UsersContainer';
import Can from '@/components/elements/Can';
import BackupContainer from '@/components/server/backups/BackupContainer';
import Spinner from '@/components/elements/Spinner';
import ScreenBlock, { NotFound, ServerError } from '@/components/elements/ScreenBlock';
import { httpErrorToHuman } from '@/api/http';
import { useStoreState } from 'easy-peasy';
import SubNavigation from '@/components/elements/SubNavigation';
import NetworkContainer from '@/components/server/network/NetworkContainer';
import InstallListener from '@/components/server/InstallListener';
import StartupContainer from '@/components/server/startup/StartupContainer';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faExternalLinkAlt } from '@fortawesome/free-solid-svg-icons';
import RequireServerPermission from '@/hoc/RequireServerPermission';
import ServerInstallSvg from '@/assets/images/server_installing.svg';
import ServerRestoreSvg from '@/assets/images/server_restore.svg';
import ServerErrorSvg from '@/assets/images/server_error.svg';
import { useTranslation } from 'react-i18next';

const ConflictStateRenderer = () => {
    const { t } = useTranslation();
    const status = ServerContext.useStoreState(state => state.server.data?.status || null);
    const isTransferring = ServerContext.useStoreState(state => state.server.data?.isTransferring || false);

    return (
        status === 'installing' || status === 'install_failed' ?
            <ScreenBlock
                title={t('Routers Server Installer Title')}
                image={ServerInstallSvg}
                message={t('Routers Server Installer Desc')}
            />
            :
            status === 'suspended' ?
                <ScreenBlock
                    title={t('Routers Server Suspended Title')}
                    image={ServerErrorSvg}
                    message={t('Routers Server Suspended Desc')}
                />
                :
                <ScreenBlock
                    title={isTransferring ? t('Routers Server Transferring Title') : t('Routers Server Restoring Title')}
                    image={ServerRestoreSvg}
                    message={isTransferring ? t('Routers Server Transferring Desc') : t('Routers Server Restoring Desc')}
                />
    );
};

const ServerRouter = ({ match, location }: RouteComponentProps<{ id: string }>) => {
    const { t } = useTranslation();
    const rootAdmin = useStoreState(state => state.user.data!.rootAdmin);
    const [ error, setError ] = useState('');

    const id = ServerContext.useStoreState(state => state.server.data?.id);
    const uuid = ServerContext.useStoreState(state => state.server.data?.uuid);
    const inConflictState = ServerContext.useStoreState(state => state.server.inConflictState);
    const serverId = ServerContext.useStoreState(state => state.server.data?.internalId);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);

    useEffect(() => () => {
        clearServerState();
    }, []);

    useEffect(() => {
        setError('');

        getServer(match.params.id)
            .catch(error => {
                console.error(error);
                setError(httpErrorToHuman(error));
            });

        return () => {
            clearServerState();
        };
    }, [ match.params.id ]);

    return (
        <React.Fragment key={'server-router'}>
            <NavigationBar/>
            {(!uuid || !id) ?
                error ?
                    <ServerError message={error}/>
                    :
                    <Spinner size={'large'} centered/>
                :
                <>
                    <CSSTransition timeout={150} classNames={'fade'} appear in>
                        <SubNavigation>
                            <div>
                                <NavLink to={`${match.url}`} exact>{t('Routers Server Title Console')}</NavLink>
                                <Can action={'file.*'}>
                                    <NavLink to={`${match.url}/files`}>{t('Routers Server Title File Manager')}</NavLink>
                                </Can>
                                <Can action={'database.*'}>
                                    <NavLink to={`${match.url}/databases`}>{t('Routers Server Title Databases')}</NavLink>
                                </Can>
                                <Can action={'schedule.*'}>
                                    <NavLink to={`${match.url}/schedules`}>{t('Routers Server Title Schedules')}</NavLink>
                                </Can>
                                <Can action={'user.*'}>
                                    <NavLink to={`${match.url}/users`}>{t('Routers Server Title Users')}</NavLink>
                                </Can>
                                <Can action={'backup.*'}>
                                    <NavLink to={`${match.url}/backups`}>{t('Routers Server Title Backups')}</NavLink>
                                </Can>
                                <Can action={'allocation.*'}>
                                    <NavLink to={`${match.url}/network`}>{t('Routers Server Title Network')}</NavLink>
                                </Can>
                                <Can action={'startup.*'}>
                                    <NavLink to={`${match.url}/startup`}>{t('Routers Server Title Startup')}</NavLink>
                                </Can>
                                <Can action={[ 'settings.*', 'file.sftp' ]} matchAny>
                                    <NavLink to={`${match.url}/settings`}>{t('Routers Server Title Settings')}</NavLink>
                                </Can>
                                {rootAdmin &&
                                <a href={'/admin/servers/view/' + serverId} rel="noreferrer" target={'_blank'}>
                                    <FontAwesomeIcon icon={faExternalLinkAlt}/>
                                </a>
                                }
                            </div>
                        </SubNavigation>
                    </CSSTransition>
                    <InstallListener/>
                    <TransferListener/>
                    <WebsocketHandler/>
                    {(inConflictState && (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${id}`)))) ?
                        <ConflictStateRenderer/>
                        :
                        <ErrorBoundary>
                            <TransitionRouter>
                                <Switch location={location}>
                                    <Route path={`${match.path}`} component={ServerConsole} exact/>
                                    <Route path={`${match.path}/files`} exact>
                                        <RequireServerPermission permissions={'file.*'}>
                                            <FileManagerContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/files/:action(edit|new)`} exact>
                                        <Spinner.Suspense>
                                            <FileEditContainer/>
                                        </Spinner.Suspense>
                                    </Route>
                                    <Route path={`${match.path}/databases`} exact>
                                        <RequireServerPermission permissions={'database.*'}>
                                            <DatabasesContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/schedules`} exact>
                                        <RequireServerPermission permissions={'schedule.*'}>
                                            <ScheduleContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/schedules/:id`} exact>
                                        <ScheduleEditContainer/>
                                    </Route>
                                    <Route path={`${match.path}/users`} exact>
                                        <RequireServerPermission permissions={'user.*'}>
                                            <UsersContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/backups`} exact>
                                        <RequireServerPermission permissions={'backup.*'}>
                                            <BackupContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/network`} exact>
                                        <RequireServerPermission permissions={'allocation.*'}>
                                            <NetworkContainer/>
                                        </RequireServerPermission>
                                    </Route>
                                    <Route path={`${match.path}/startup`} component={StartupContainer} exact/>
                                    <Route path={`${match.path}/settings`} component={SettingsContainer} exact/>
                                    <Route path={'*'} component={NotFound}/>
                                </Switch>
                            </TransitionRouter>
                        </ErrorBoundary>
                    }
                </>
            }
        </React.Fragment>
    );
};

export default (props: RouteComponentProps<any>) => (
    <ServerContext.Provider>
        <ServerRouter {...props}/>
    </ServerContext.Provider>
);
