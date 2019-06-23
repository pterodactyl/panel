import * as React from 'react';
import { Route, RouteComponentProps } from 'react-router-dom';
import DesignElementsContainer from '@/components/account/DesignElementsContainer';
import AccountOverviewContainer from '@/components/account/AccountOverviewContainer';

export default ({ match }: RouteComponentProps) => (
    <div>
        <Route path={`${match.path}/`} component={AccountOverviewContainer} exact/>
        <Route path={`${match.path}/design`} component={DesignElementsContainer} exact/>
    </div>
);
