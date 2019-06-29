import React, { useEffect } from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';
import { Actions, State, useStoreActions, useStoreState } from 'easy-peasy';
import { ApplicationState } from '@/state/types';
import Spinner from '@/components/elements/Spinner';
import WebsocketHandler from '@/components/server/WebsocketHandler';

export default ({ match, location }: RouteComponentProps<{ id: string }>) => {
    const server = useStoreState((state: State<ApplicationState>) => state.server.data);
    const { clearServerState, getServer } = useStoreActions((actions: Actions<ApplicationState>) => actions.server);

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
                            </Switch>
                        </React.Fragment>
                    }
                </div>
            </TransitionRouter>
        </React.Fragment>
    );
};
