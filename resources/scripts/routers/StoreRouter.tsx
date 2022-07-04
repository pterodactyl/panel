import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { useStoreState } from 'easy-peasy';
import { useLocation } from 'react-router';
import TransitionRouter from '@/TransitionRouter';
import SidePanel from '@/components/elements/SidePanel';
import { NotFound } from '@/components/elements/ScreenBlock';
import EarnContainer from '@/components/store/EarnContainer';
import SubNavigation from '@/components/elements/SubNavigation';
import useWindowDimensions from '@/plugins/useWindowDimensions';
import EditContainer from '@/components/store/edit/EditContainer';
import BalanceContainer from '@/components/store/BalanceContainer';
import OverviewContainer from '@/components/store/OverviewContainer';
import MobileNavigation from '@/components/elements/MobileNavigation';
import CreateContainer from '@/components/store/create/CreateContainer';
import { NavLink, Route, Switch, useRouteMatch } from 'react-router-dom';
import ProductsContainer from '@/components/store/resources/ProductsContainer';

const StoreRouter = () => {
    const match = useRouteMatch<{ id: string }>();
    const location = useLocation();
    const { width } = useWindowDimensions();
    const earn = useStoreState((state) => state.storefront.data!.earn);

    return (
        <>
            {width >= 1280 ? <SidePanel /> : <MobileNavigation />}
            <SubNavigation>
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
                    {earn.enabled === 'true' && (
                        <NavLink to={`${match.url}/earn`}>
                            <div css={tw`flex items-center justify-between`}>
                                Earn Credits <Icon.DollarSign css={tw`ml-1`} size={18} />
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
                        <ProductsContainer />
                    </Route>
                    <Route path={`${match.path}/create`} exact>
                        <CreateContainer />
                    </Route>
                    <Route path={`${match.path}/edit`} exact>
                        <EditContainer />
                    </Route>
                    {earn.enabled === 'true' && (
                        <Route path={`${match.path}/earn`} exact>
                            <EarnContainer />
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
