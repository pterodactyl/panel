import React from 'react';
import { store } from '@/state';
import { StoreProvider } from 'easy-peasy';
import { hot } from 'react-hot-loader/root';
import { history } from '@/components/history';
import IndexRouter from '@/routers/IndexRouter';
import { SiteSettings } from '@/state/settings';
import { setupInterceptors } from '@/api/interceptors';
import { StorefrontSettings } from '@/state/storefront';
import ProgressBar from '@/components/elements/ProgressBar';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import tw, { GlobalStyles as TailwindGlobalStyles } from 'twin.macro';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
    StoreConfiguration?: StorefrontSettings;
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        /* eslint-disable camelcase */
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        store_balance: number;
        store_cpu: number;
        store_memory: number;
        store_disk: number;
        updated_at: string;
        created_at: string;
        /* eslint-enable camelcase */
    };
}

setupInterceptors(history);

const App = () => {
    const {
        PterodactylUser,
        SiteConfiguration,
        StoreConfiguration,
    } = (window as ExtendedWindow);

    if (PterodactylUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: PterodactylUser.uuid,
            username: PterodactylUser.username,
            email: PterodactylUser.email,
            language: PterodactylUser.language,
            rootAdmin: PterodactylUser.root_admin,
            useTotp: PterodactylUser.use_totp,
            storeBalance: PterodactylUser.store_balance,
            storeCpu: PterodactylUser.store_cpu,
            storeMemory: PterodactylUser.store_memory,
            storeDisk: PterodactylUser.store_disk,
            createdAt: new Date(PterodactylUser.created_at),
            updatedAt: new Date(PterodactylUser.updated_at),
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
    }

    if (!store.getState().storefront.data) {
        store.getActions().storefront.setStorefront(StoreConfiguration!);
    }

    return (
        <>
            <GlobalStylesheet/>
            <TailwindGlobalStyles/>
            <StoreProvider store={store}>
                <ProgressBar/>
                <div css={tw`mx-auto w-auto`}>
                    <IndexRouter />
                </div>
            </StoreProvider>
        </>
    );
};

export default hot(App);
