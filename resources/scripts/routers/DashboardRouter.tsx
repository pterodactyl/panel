import * as React from 'react';
import { Route, RouteComponentProps } from 'react-router-dom';
import DesignElementsContainer from '@/components/dashboard/DesignElementsContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';

export default ({ match }: RouteComponentProps) => (
    <div>
        <NavigationBar/>
        <div className={'w-full mx-auto'} style={{ maxWidth: '1200px' }}>
            <Route path={'/'} component={DashboardContainer} exact/>
            <Route path={'/account'} component={AccountOverviewContainer}/>
            <Route path={'/design'} component={DesignElementsContainer}/>
        </div>
    </div>
);
