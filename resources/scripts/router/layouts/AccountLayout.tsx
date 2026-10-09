import { Outlet } from '@tanstack/react-router';
import SubNavigation from '@/components/elements/SubNavigation';
import SubNavigationLayout from '@/components/elements/SubNavigationLayout';
import NavLink from '@/router/NavLink';
import { getAreaNav } from '@/router/nav';
import Slot from '@/extensions/Slot';
import NavigationLabel from '@/extensions/NavigationLabel';
import { ScreenGate } from '@/extensions/screenNavigation';

export default function AccountLayout() {
    return (
        <SubNavigationLayout
            navigation={
                <SubNavigation>
                    <Slot name='account.navigation.before' />
                    {getAreaNav('account').map(({ segment, label, exact = false, ...meta }) => (
                        <ScreenGate key={segment || '/'} screen={meta.screen}>
                            <NavLink
                                to={`/account${segment ? `/${segment}` : ''}`}
                                exact={exact}
                                data-core={meta.screen ? undefined : ''}
                            >
                                <NavigationLabel label={label} {...meta} />
                            </NavLink>
                        </ScreenGate>
                    ))}
                    <Slot name='account.navigation.after' />
                </SubNavigation>
            }
        >
            <Outlet />
        </SubNavigationLayout>
    );
}
