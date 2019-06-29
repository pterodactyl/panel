import * as React from 'react';
import { Route, RouteComponentProps, Switch } from 'react-router-dom';
import DesignElementsContainer from '@/components/dashboard/DesignElementsContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import TransitionRouter from '@/TransitionRouter';

export default ({ location }: RouteComponentProps) => (
    <React.Fragment>
        <NavigationBar/>
        <TransitionRouter>
            <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
                <Switch location={location}>
                    <Route path={'/'} component={DashboardContainer} exact/>
                    <Route path={'/account'} component={AccountOverviewContainer}/>
                    <Route path={'/design'} component={DesignElementsContainer}/>
                </Switch>
            </div>
        </TransitionRouter>
    </React.Fragment>
);
