import React from 'react';
import tw from 'twin.macro';
import '@/assets/tailwind.css';
import { store } from '@/state';
import { StoreProvider } from 'easy-peasy';
import { hot } from 'react-hot-loader/root';
import { history } from '@/components/history';
import { SiteSettings } from '@/state/settings';
import IndexRouter from '@/routers/IndexRouter';
import { setupInterceptors } from '@/api/interceptors';
import { StorefrontSettings } from '@/state/storefront';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';

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
        store_slots: number;
        store_ports: number;
        store_backups: number;
        store_databases: number;
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
            store: {
                balance: PterodactylUser.store_balance,
                cpu: PterodactylUser.store_cpu,
                memory: PterodactylUser.store_memory,
                disk: PterodactylUser.store_disk,
                slots: PterodactylUser.store_slots,
                ports: PterodactylUser.store_ports,
                backups: PterodactylUser.store_backups,
                databases: PterodactylUser.store_databases,
            },
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
            <StoreProvider store={store}>
                <div css={tw`mx-auto w-auto`}>
                    <IndexRouter />
                </div>
            </StoreProvider>
        </>
    );
};

export default hot(App);
