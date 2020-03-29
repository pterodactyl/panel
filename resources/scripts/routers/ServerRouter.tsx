import React, { useEffect } from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import Spinner from '@/components/elements/Spinner';
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

const ServerRouter = ({ match, location }: RouteComponentProps<{ id: string }>) => {
    const server = ServerContext.useStoreState(state => state.server.data);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);

    if (!server) {
        getServer(match.params.id);
    }

    useEffect(() => () => clearServerState(), [ clearServerState ]);

    return (
        <React.Fragment>
            <NavigationBar/>
            <CSSTransition timeout={250} classNames={'fade'} appear={true} in={true}>
                <div id={'sub-navigation'}>
                    <div className={'items'}>
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
                        <Can action={'settings.*'}>
                            <NavLink to={`${match.url}/settings`}>Settings</NavLink>
                        </Can>
                    </div>
                </div>
            </CSSTransition>
            <WebsocketHandler/>
            <TransitionRouter>
                {!server ?
                    <div className={'flex justify-center m-20'}>
                        <Spinner size={'large'}/>
                    </div>
                    :
                    <React.Fragment>
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
                            <Route path={`${match.path}/schedules/:id`} component={ScheduleEditContainer} exact/>
                            <Route path={`${match.path}/users`} component={UsersContainer} exact/>
                            <Route path={`${match.path}/settings`} component={SettingsContainer} exact/>
                        </Switch>
                    </React.Fragment>
                }
            </TransitionRouter>
        </React.Fragment>
    );
};

export default (props: RouteComponentProps<any>) => (
    <ServerContext.Provider>
        <ServerRouter {...props}/>
    </ServerContext.Provider>
);
