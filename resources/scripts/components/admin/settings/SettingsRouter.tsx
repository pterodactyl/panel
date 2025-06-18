import { ChipIcon, CodeIcon, MailIcon, ShieldCheckIcon } from '@heroicons/react/outline';
import { Route, Routes } from 'react-router-dom';
import tw from 'twin.macro';

import AdminContentBlock from '@/components/admin/AdminContentBlock';
import MailSettings from '@/components/admin/settings/MailSettings';
import FlashMessageRender from '@/components/FlashMessageRender';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import GeneralSettings from '@/components/admin/settings/GeneralSettings';
import SecuritySettings from '@/components/admin/settings/SecuritySettings';
import AdvancedSettings from './AdvancedSettings';
import { Settings, getSettings } from '@/api/admin/settings';
import { useEffect, useState } from 'react';
import { Action, Actions, action, createContextStore, useStoreActions } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import Spinner from '@/components/elements/Spinner';

interface ctx {
    settings: Settings | undefined;
    setSettings: Action<ctx, Settings | undefined>;
}

export const Context = createContextStore<ctx>({
    settings: undefined,

    setSettings: action((state, payload) => {
        state.settings = payload;
    }),
});

const SettingsRouter = () => {
    const { clearFlashes, clearAndAddHttpError } = useStoreActions(
        (actions: Actions<ApplicationStore>) => actions.flashes,
    );
    const [loading, setLoading] = useState(true);

    const settings = Context.useStoreState(state => state.settings);
    const setSettings = Context.useStoreActions(actions => actions.setSettings);

    useEffect(() => {
        clearFlashes('settings');

        getSettings()
            .then(settings => setSettings(settings))
            .catch(error => {
                console.error(error);
                clearAndAddHttpError({ key: 'settings', error });
            })
            .then(() => setLoading(false));
    }, []);

    if (loading || settings === undefined) {
        return (
            <AdminContentBlock>
                <FlashMessageRender byKey={'settings'} css={tw`mb-4`} />

                <div css={tw`w-full flex flex-col items-center justify-center`} style={{ height: '24rem' }}>
                    <Spinner size={'base'} />
                </div>
            </AdminContentBlock>
        );
    }

    return (
        <AdminContentBlock title={'Settings'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Settings</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>
                        Configure and manage settings for Pterodactyl.
                    </p>
                </div>
            </div>

            <FlashMessageRender byKey={'admin:settings'} css={tw`mb-4`} />

            <SubNavigation>
                <SubNavigationLink to="/admin/settings" name="General">
                    <ChipIcon />
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/mail" name="Mail">
                    <MailIcon />
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/security" name="Security">
                    <ShieldCheckIcon />
                </SubNavigationLink>
                {/* <SubNavigationLink to="/admin/settings/features" name="Features">
                    <AdjustmentsIcon />
                </SubNavigationLink> */}
                <SubNavigationLink to="/admin/settings/advanced" name="Advanced">
                    <CodeIcon />
                </SubNavigationLink>
            </SubNavigation>

            <Routes>
                <Route path="/" element={<GeneralSettings />} />
                <Route path="/mail" element={<MailSettings />} />
                <Route path="/security" element={<SecuritySettings />} />
                {/* <Route path="/features" element={<p>Features</p>} /> */}
                <Route path="/advanced" element={<AdvancedSettings />} />
            </Routes>
        </AdminContentBlock>
    );
};

export default () => {
    return (
        <Context.Provider>
            <SettingsRouter />
        </Context.Provider>
    );
};
