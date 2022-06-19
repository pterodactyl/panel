import tw from 'twin.macro';
import '@/assets/tailwind.css';
import { store } from '@/state';
import React, { useEffect } from 'react';
import { StoreProvider } from 'easy-peasy';
import { hot } from 'react-hot-loader/root';
import { history } from '@/components/history';
import { SiteSettings } from '@/state/settings';
import IndexRouter from '@/routers/IndexRouter';
import { setupInterceptors } from '@/api/interceptors';
import { StorefrontSettings } from '@/state/storefront';
import GlobalStylesheet from '@/assets/css/GlobalStylesheet';
import earnCredits from '@/api/account/earnCredits';

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

    function wait (ms: number) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async function earn () {
        await wait(60000);
        earnCredits()
            .catch(() => console.error('Failed to add credits'));
    }

    useEffect(() => {
        earn();
    }, []);

    setInterval(earn, 60000);

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
