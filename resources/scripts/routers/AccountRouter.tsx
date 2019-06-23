import * as React from 'react';
import { Route } from 'react-router-dom';
import DesignElements from '@/components/account/DesignElements';
import TransitionRouter from '@/TransitionRouter';

export default () => (
    <TransitionRouter basename={'/account'}>
        <Route path={'/'} component={DesignElements} exact/>
        <Route path={'/design'} component={DesignElements} exact/>
    </TransitionRouter>
);
