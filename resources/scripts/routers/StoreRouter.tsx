import React from 'react';
import { useStoreState } from 'easy-peasy';
import { useLocation } from 'react-router';
import TransitionRouter from '@/TransitionRouter';
import SidePanel from '@/components/elements/SidePanel';
import { NotFound } from '@/components/elements/ScreenBlock';
import useWindowDimensions from '@/plugins/useWindowDimensions';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import CreateContainer from '@/components/store/CreateContainer';
import BalanceContainer from '@/components/store/BalanceContainer';
import ReferralContainer from '@/components/store/ReferralContainer';
import OverviewContainer from '@/components/store/OverviewContainer';
import MobileNavigation from '@/components/elements/MobileNavigation';
import ResourcesContainer from '@/components/store/ResourcesContainer';

const StoreRouter = () => {
    const location = useLocation();
    const { width } = useWindowDimensions();
    const match = useRouteMatch<{ id: string }>();
    const referrals = useStoreState((state) => state.storefront.data!.referrals);

    return (
        <>
            {width >= 1280 ? <SidePanel /> : <MobileNavigation />}
            <TransitionRouter>
                <Switch location={location}>
                    <Route path={`${match.path}`} exact>
                        <OverviewContainer />
                    </Route>
                    <Route path={`${match.path}/balance`} exact>
                        <BalanceContainer />
                    </Route>
                    <Route path={`${match.path}/resources`} exact>
                        <ResourcesContainer />
                    </Route>
                    <Route path={`${match.path}/create`} exact>
                        <CreateContainer />
                    </Route>
                    {referrals.enabled && (
                        <Route path={`${match.path}/referrals`} exact>
                            <ReferralContainer />
                        </Route>
                    )}
                    <Route path={'*'}>
                        <NotFound />
                    </Route>
                </Switch>
            </TransitionRouter>
        </>
    );
};

export default StoreRouter;
