import MailSettings from '@/components/admin/settings/MailSettings';
import { AdjustmentsIcon, ChipIcon, CodeIcon, MailIcon, ShieldCheckIcon } from '@heroicons/react/outline';
import React from 'react';
import { Route, useLocation } from 'react-router';
import { Switch } from 'react-router-dom';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import { SubNavigation, SubNavigationLink } from '@/components/admin/SubNavigation';
import GeneralSettings from '@/components/admin/settings/GeneralSettings';

export default () => {
    const location = useLocation();

    return (
        <AdminContentBlock title={'Settings'}>
            <div css={tw`w-full flex flex-row items-center mb-8`}>
                <div css={tw`flex flex-col flex-shrink`} style={{ minWidth: '0' }}>
                    <h2 css={tw`text-2xl text-neutral-50 font-header font-medium`}>Settings</h2>
                    <p css={tw`text-base text-neutral-400 whitespace-nowrap overflow-ellipsis overflow-hidden`}>Configure and manage settings for Pterodactyl.</p>
                </div>
            </div>

            <FlashMessageRender byKey={'settings'} css={tw`mb-4`}/>

            <SubNavigation>
                <SubNavigationLink to="/admin/settings" name="General">
                    <ChipIcon/>
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/mail" name="Mail">
                    <MailIcon/>
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/security" name="Security">
                    <ShieldCheckIcon/>
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/features" name="Features">
                    <AdjustmentsIcon/>
                </SubNavigationLink>
                <SubNavigationLink to="/admin/settings/advanced" name="Advanced">
                    <CodeIcon/>
                </SubNavigationLink>
            </SubNavigation>

            <Switch location={location}>
                <Route path="/admin/settings" exact>
                    <GeneralSettings/>
                </Route>
                <Route path="/admin/settings/mail" exact>
                    <MailSettings/>
                </Route>
                <Route path="/admin/settings/security" exact>
                    <p>Security</p>
                </Route>
                <Route path="/admin/settings/features" exact>
                    <p>Features</p>
                </Route>
                <Route path="/admin/settings/advanced" exact>
                    <p>Advanced</p>
                </Route>
            </Switch>
        </AdminContentBlock>
    );
};
