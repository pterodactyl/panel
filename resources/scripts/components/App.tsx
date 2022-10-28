import { StoreProvider } from 'easy-peasy';
import { lazy } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';
import tw from 'twin.macro';

import '@/assets/tailwind.css';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import AuthenticatedRoute from '@/components/elements/AuthenticatedRoute';
import ProgressBar from '@/components/elements/ProgressBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import { store } from '@/state';
import { SiteSettings } from '@/state/settings';

const DashboardRouter = lazy(() => import(/* webpackChunkName: "dashboard" */ '@/routers/DashboardRouter'));
const ServerRouter = lazy(() => import(/* webpackChunkName: "server" */ '@/routers/ServerRouter'));
const AuthenticationRouter = lazy(() => import(/* webpackChunkName: "auth" */ '@/routers/AuthenticationRouter'));

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        /* eslint-disable camelcase */
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        updated_at: string;
        created_at: string;
        /* eslint-enable camelcase */
    };
}

// setupInterceptors(history);

function App() {
    const { PterodactylUser, SiteConfiguration } = window as ExtendedWindow;
    if (PterodactylUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: PterodactylUser.uuid,
            username: PterodactylUser.username,
            email: PterodactylUser.email,
            language: PterodactylUser.language,
            rootAdmin: PterodactylUser.root_admin,
            useTotp: PterodactylUser.use_totp,
            createdAt: new Date(PterodactylUser.created_at),
            updatedAt: new Date(PterodactylUser.updated_at),
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    return (
        <>
            {/* @ts-expect-error go away */}
            <GlobalStylesheet />

            <StoreProvider store={store}>
                <ProgressBar />

                <div css={tw`mx-auto w-auto`}>
                    <BrowserRouter>
                        <Routes>
                            <Route
                                path="/auth/*"
                                element={
                                    <Spinner.Suspense>
                                        <AuthenticationRouter />
                                    </Spinner.Suspense>
                                }
                            />

                            <Route
                                path="/server/:id/*"
                                element={
                                    <AuthenticatedRoute>
                                        <Spinner.Suspense>
                                            <ServerRouter />
                                        </Spinner.Suspense>
                                    </AuthenticatedRoute>
                                }
                            />

                            <Route
                                path="/*"
                                element={
                                    <AuthenticatedRoute>
                                        <Spinner.Suspense>
                                            <DashboardRouter />
                                        </Spinner.Suspense>
                                    </AuthenticatedRoute>
                                }
                            />

                            <Route path="*" element={<NotFound />} />
                        </Routes>
                    </BrowserRouter>
                </div>
            </StoreProvider>
        </>
    );
}

export { App };
