import { Suspense } from 'react';
import { NavLink, Route, Routes, useLocation } from 'react-router-dom';

import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import SubNavigation from '@/components/elements/SubNavigation';
import routes from '@/routers/routes';

function DashboardRouter() {
    const location = useLocation();

    return (
        <>
            <NavigationBar />

            {location.pathname.startsWith('/account') && (
                <SubNavigation>
                    <div>
                        {routes.account
                            .filter(route => route.path !== undefined)
                            .map(({ path, name, end = false }) => (
                                <NavLink key={path} to={`/account/${path ?? ''}`.replace(/\/$/, '')} end={end}>
                                    {name}
                                </NavLink>
                            ))}
                    </div>
                </SubNavigation>
            )}

            <Suspense fallback={<Spinner centered />}>
                <Routes>
                    <Route path="" element={<DashboardContainer />} />

                    {routes.account.map(({ route, component: Component }) => (
                        <Route key={route} path={`/account/${route}`.replace(/\/$/, '')} element={<Component />} />
                    ))}

                    <Route path="*" element={<NotFound />} />
                </Routes>
            </Suspense>
        </>
    );
}

export default DashboardRouter;
