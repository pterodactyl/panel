import React, { useEffect } from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import Spinner from '@/components/elements/Spinner';
import WebsocketHandler from '@/components/server/WebsocketHandler';
import { ServerContext } from '@/state/server';
import { Provider } from 'react-redux';
import DatabasesContainer from '@/components/server/databases/DatabasesContainer';
import FileManagerContainer from '@/components/server/files/FileManagerContainer';
import { CSSTransition } from 'react-transition-group';
import FileEditContainer from '@/components/server/files/FileEditContainer';

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
            <CSSTransition timeout={250} classNames={'fade'} appear={true} in={true}>
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
            </CSSTransition>
            <Provider store={ServerContext.useStore()}>
                <TransitionRouter>
                    <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
                        {!server ?
                            <div className={'flex justify-center m-20'}>
                                <Spinner size={'large'}/>
                            </div>
                            :
                            <React.Fragment>
                                <WebsocketHandler/>
                                <Switch location={location}>
                                    <Route path={`${match.path}`} component={ServerConsole} exact/>
                                    <Route path={`${match.path}/files`} component={FileManagerContainer} exact/>
                                    <Route path={`${match.path}/files/edit`} component={FileEditContainer} exact/>
                                    <Route path={`${match.path}/databases`} component={DatabasesContainer}/>
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
