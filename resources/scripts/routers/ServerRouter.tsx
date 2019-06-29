import * as React from 'react';
import { Route, RouteComponentProps, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import ServerConsole from '@/components/server/ServerConsole';
import TransitionRouter from '@/TransitionRouter';

export default ({ location }: RouteComponentProps) => (
    <div>
        <NavigationBar/>
        <TransitionRouter>
            <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
                <Switch location={location}>
                    <Route path={`/`} component={ServerConsole} exact/>
                </Switch>
            </div>
        </TransitionRouter>
    </div>
);
