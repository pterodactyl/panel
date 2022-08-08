import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { useStoreState } from 'easy-peasy';
import { useLocation } from 'react-router';
import TransitionRouter from '@/TransitionRouter';
import SidePanel from '@/components/elements/SidePanel';
import { NotFound } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import useWindowDimensions from '@/plugins/useWindowDimensions';
import CreateContainer from '@/components/store/CreateContainer';
import BalanceContainer from '@/components/store/BalanceContainer';
import ReferralContainer from '@/components/store/ReferralContainer';
import OverviewContainer from '@/components/store/OverviewContainer';
import MobileNavigation from '@/components/elements/MobileNavigation';
import ResourcesContainer from '@/components/store/ResourcesContainer';
import { NavLink, Route, Switch, useRouteMatch } from 'react-router-dom';

const StoreRouter = () => {
    const match = useRouteMatch<{ id: string }>();
    const location = useLocation();
    const { width } = useWindowDimensions();
    const referrals = useStoreState((state) => state.storefront.data!.referrals);

    return (
        <>
            {width >= 1280 ? <SidePanel /> : <MobileNavigation />}
            <SubNavigation className={'j-down'}>
                <div>
                    <NavLink to={`${match.url}`} exact>
                        <div css={tw`flex items-center justify-between`}>
                            Overview <Icon.Home css={tw`ml-1`} size={18} />
                        </div>
                    </NavLink>
                    <NavLink to={`${match.url}/balance`}>
                        <div css={tw`flex items-center justify-between`}>
                            Balance <Icon.DollarSign css={tw`ml-1`} size={18} />
                        </div>
                    </NavLink>
                    <NavLink to={`${match.url}/resources`}>
                        <div css={tw`flex items-center justify-between`}>
                            Resources <Icon.ShoppingCart css={tw`ml-1`} size={18} />
                        </div>
                    </NavLink>
                    {referrals.enabled && (
                        <NavLink to={`${match.url}/referrals`}>
                            <div css={tw`flex items-center justify-between`}>
                                Referrals <Icon.Users css={tw`ml-1`} size={18} />
                            </div>
                        </NavLink>
                    )}
                </div>
            </SubNavigation>
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
