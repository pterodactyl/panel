import * as React from 'react';
import { Route, RouteComponentProps } from 'react-router-dom';
import DesignElements from '@/components/account/DesignElements';

export default ({ match }: RouteComponentProps) => (
    <div>
        <Route path={`${match.path}/`} component={DesignElements} exact/>
        <Route path={`${match.path}/design`} component={DesignElements} exact/>
    </div>
);
