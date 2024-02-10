import { StoreProvider } from 'easy-peasy';
import { lazy } from 'react';
import { BrowserRouter, Route, Routes } from 'react-router-dom';

import '@/assets/tailwind.css';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import AuthenticatedRoute from '@/components/elements/AuthenticatedRoute';
import ProgressBar from '@/components/elements/ProgressBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import { store } from '@/state';
import { ServerContext } from '@/state/server';
import { SiteSettings } from '@/state/settings';
import { AdminContext } from '@/state/admin';

const AdminRouter = lazy(() => import('@/routers/AdminRouter'));
const AuthenticationRouter = lazy(() => import('@/routers/AuthenticationRouter'));
const DashboardRouter = lazy(() => import('@/routers/DashboardRouter'));
const ServerRouter = lazy(() => import('@/routers/ServerRouter'));

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
        avatar_url: string;
        admin_role_name: string;
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
            avatarURL: PterodactylUser.avatar_url,
            roleName: PterodactylUser.admin_role_name,
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

                <div className="mx-auto w-auto">
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
                                            <ServerContext.Provider>
                                                <ServerRouter />
                                            </ServerContext.Provider>
                                        </Spinner.Suspense>
                                    </AuthenticatedRoute>
                                }
                            />

                            <Route
                                path="/admin/*"
                                element={
                                    <Spinner.Suspense>
                                        <AdminContext.Provider>
                                            <AdminRouter />
                                        </AdminContext.Provider>
                                    </Spinner.Suspense>
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
