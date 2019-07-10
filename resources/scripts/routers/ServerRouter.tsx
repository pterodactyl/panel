import React, { useEffect } from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import Spinner from '@/components/elements/Spinner';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import ServerDatabases from '@/components/server/ServerDatabases';
import { ServerContext } from '@/state/server';
import { Provider } from 'react-redux';

const ServerRouter = ({ match, location }: RouteComponentProps<{ id: string }>) => {
    const server = ServerContext.useStoreState(state => state.server.data);
    const getServer = ServerContext.useStoreActions(actions => actions.server.getServer);
    const clearServerState = ServerContext.useStoreActions(actions => actions.clearServerState);

    if (!server) {
        getServer(match.params.id);
    }

    useEffect(() => () => clearServerState(), []);

    return (
        <React.Fragment>
            <NavigationBar/>
            <div id={'sub-navigation'}>
                <div className={'mx-auto'} style={{ maxWidth: '1200px' }}>
                    <div className={'items'}>
                        <NavLink to={`${match.url}`} exact>Console</NavLink>
                        <NavLink to={`${match.url}/files`}>File Manager</NavLink>
                        <NavLink to={`${match.url}/databases`}>Databases</NavLink>
                        <NavLink to={`${match.url}/users`}>User Management</NavLink>
                    </div>
                </div>
            </div>
            <Provider store={ServerContext.useStore()}>
                <TransitionRouter>
                    <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
                        {!server ?
                            <div className={'flex justify-center m-20'}>
                                <Spinner large={true}/>
                            </div>
                            :
                            <React.Fragment>
                                <WebsocketHandler/>
                                <Switch location={location}>
                                    <Route path={`${match.path}`} component={ServerConsole} exact/>
                                    <Route path={`${match.path}/databases`} component={ServerDatabases}/>
                                </Switch>
                            </React.Fragment>
                        }
                    </div>
                </TransitionRouter>
            </Provider>
        </React.Fragment>
    );
};

export default (props: RouteComponentProps<any>) => (
    <ServerContext.Provider>
        <ServerRouter {...props}/>
    </ServerContext.Provider>
);
