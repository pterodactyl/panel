import * as React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';

export default ({ match, location }: RouteComponentProps) => (
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
                <Switch location={location}>
                    <Route path={`${match.path}`} component={ServerConsole} exact/>
                </Switch>
            </div>
        </TransitionRouter>
    </React.Fragment>
);
