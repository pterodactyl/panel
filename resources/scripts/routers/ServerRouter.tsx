import React, { useEffect, useState } from 'react';
import ReactGA from 'react-ga';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import DatabasesContainer from '@/components/server/databases/DatabasesContainer';
import FileManagerContainer from '@/components/server/files/FileManagerContainer';
import { CSSTransition } from 'react-transition-group';
import SuspenseSpinner from '@/components/elements/SuspenseSpinner';
import FileEditContainer from '@/components/server/files/FileEditContainer';
import SettingsContainer from '@/components/server/settings/SettingsContainer';
import ScheduleContainer from '@/components/server/schedules/ScheduleContainer';
import ScheduleEditContainer from '@/components/server/schedules/ScheduleEditContainer';
import UsersContainer from '@/components/server/users/UsersContainer';
import Can from '@/components/elements/Can';
import BackupContainer from '@/components/server/backups/BackupContainer';
import Spinner from '@/components/elements/Spinner';
import ServerError from '@/components/screens/ServerError';
import { httpErrorToHuman } from '@/api/http';
import NotFound from '@/components/screens/NotFound';
import { useStoreState } from 'easy-peasy';
import useServer from '@/plugins/useServer';
import ScreenBlock from '@/components/screens/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import NetworkContainer from '@/components/server/network/NetworkContainer';
import InstallListener from '@/components/server/InstallListener';

const ServerRouter = ({ match, location }: RouteComponentProps<{ id: string }>) => {
    const { rootAdmin } = useStoreState(state => state.user.data!);
    const [ error, setError ] = useState('');
    const [ installing, setInstalling ] = useState(false);
    const server = useServer();
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);

    useEffect(() => () => {
        clearServerState();
    }, []);

    useEffect(() => {
        setInstalling(server?.isInstalling !== false);
    }, [ server?.isInstalling ]);

    useEffect(() => {
        setError('');
        setInstalling(false);
        getServer(match.params.id)
            .catch(error => {
                if (error.response?.status === 409) {
                    setInstalling(true);
                } else {
                    console.error(error);
                    setError(httpErrorToHuman(error));
                }
            });

        return () => {
            clearServerState();
        };
    }, [ match.params.id ]);

    useEffect(() => {
        ReactGA.pageview(location.pathname);
    }, [ location.pathname ]);

    return (
        <React.Fragment key={'server-router'}>
            <NavigationBar/>
            {!server ?
                error ?
                    <ServerError message={error}/>
                    :
                    <Spinner size={'large'} centered/>
                :
                <>
                    <CSSTransition timeout={150} classNames={'fade'} appear in>
                        <SubNavigation>
                            <div>
                                <NavLink to={`${match.url}`} exact>Console</NavLink>
                                <Can action={'file.*'}>
                                    <NavLink to={`${match.url}/files`}>File Manager</NavLink>
                                </Can>
                                <Can action={'database.*'}>
                                    <NavLink to={`${match.url}/databases`}>Databases</NavLink>
                                </Can>
                                <Can action={'schedule.*'}>
                                    <NavLink to={`${match.url}/schedules`}>Schedules</NavLink>
                                </Can>
                                <Can action={'user.*'}>
                                    <NavLink to={`${match.url}/users`}>Users</NavLink>
                                </Can>
                                <Can action={'backup.*'}>
                                    <NavLink to={`${match.url}/backups`}>Backups</NavLink>
                                </Can>
                                <Can action={'allocations.*'}>
                                    <NavLink to={`${match.url}/network`}>Network</NavLink>
                                </Can>
                                <Can action={[ 'settings.*', 'file.sftp' ]} matchAny>
                                    <NavLink to={`${match.url}/settings`}>Settings</NavLink>
                                </Can>
                            </div>
                        </SubNavigation>
                    </CSSTransition>
                    <InstallListener/>
                    <WebsocketHandler/>
                    {(installing && (!rootAdmin || (rootAdmin && !location.pathname.endsWith(`/server/${server.id}`)))) ?
                        <ScreenBlock
                            title={'Your server is installing.'}
                            image={'/assets/svgs/server_installing.svg'}
                            message={'Please check back in a few minutes.'}
                        />
                        :
                        <>
                            <TransitionRouter>
                                <Switch location={location}>
                                    <Route path={`${match.path}`} component={ServerConsole} exact/>
                                    <Route path={`${match.path}/files`} component={FileManagerContainer} exact/>
                                    <Route
                                        path={`${match.path}/files/:action(edit|new)`}
                                        render={props => (
                                            <SuspenseSpinner>
                                                <FileEditContainer {...props as any}/>
                                            </SuspenseSpinner>
                                        )}
                                        exact
                                    />
                                    <Route path={`${match.path}/databases`} component={DatabasesContainer} exact/>
                                    <Route path={`${match.path}/schedules`} component={ScheduleContainer} exact/>
                                    <Route
                                        path={`${match.path}/schedules/:id`}
                                        component={ScheduleEditContainer}
                                        exact
                                    />
                                    <Route path={`${match.path}/users`} component={UsersContainer} exact/>
                                    <Route path={`${match.path}/backups`} component={BackupContainer} exact/>
                                    <Route path={`${match.path}/network`} component={NetworkContainer} exact/>
                                    <Route path={`${match.path}/settings`} component={SettingsContainer} exact/>
                                    <Route path={'*'} component={NotFound}/>
                                </Switch>
                            </TransitionRouter>
                        </>
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
